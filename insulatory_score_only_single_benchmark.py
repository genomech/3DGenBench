__author__ = "Polina Belokopytova & Emil Viesná"
__version__ = "0.1b"
__date__ = "2021 Nov 1"

from contextlib import contextmanager
from cooltools.api import insulation
from hicreppy import hicrep
from multiprocessing import cpu_count
from sklearn.metrics import precision_recall_curve, auc
import scipy.stats as stats
import argparse
import cooler
import datetime
import hashlib
import json
import logging
import matplotlib.pyplot as plt
import numpy
import os, sys
import pandas, pandarallel
import subprocess
import sqlite3
import tempfile
import time
import warnings

import requests
from benchmark_whole_genome_pipeline import CreateParser, ConfigureLogger, Timer, Cool2Cool, MakeMcool, PearsonCorr, MakeBedgraph
warnings.simplefilter(action='ignore', category=FutureWarning)
pandarallel.pandarallel.initialize(nb_workers=cpu_count(), verbose=0)
# ------======| CONST |======------

C_SCC_MAXDIST = 1500000
C_SCC_H = 2
C_CONTACT_COEF = 10000
C_RANDOM_INTER_N = 5000
C_RANDOM_INTER_SIGMA = 2


def InsulationData(Datasets, Window, prediction_start, prediction_end):
    for Line in [f"Window: {Window}"]: logging.info(Line)
    InsScores = {}
    for Type, Data in Datasets.items():
        if Type == "Exp":
            InsScores[Type] = insulation.calculate_insulation_score(Data, [Window], ignore_diags=2, append_raw_scores=True).rename(
                columns={f"sum_balanced_{Window}": f"sum_balanced_{Type}"})
        else:
            InsScores[Type] = pandas.read_csv(Data, sep="\t", names=["chrom", "start", "end", "sum_balanced_"+Type])
    Result = None
    for Type, Data in InsScores.items(): Result = Data.copy() if Result is None else pandas.merge(Result, Data,
                                                                                                  how="inner",
                                                                                                  on=["chrom", "start",
                                                                                                      "end"])
    Result = Result[["chrom", "start", "end"] + [f"sum_balanced_{Type}" for Type in InsScores.keys()]]
    Result = Result.query("start >= @prediction_start & end <= @prediction_end")
    return Result

# ------======| FILE CREATOR |======------
def CreateDataFiles(UnitID, AuthorName, ModelName, SampleName, FileNamesInput, CoolDir, Chrom, PredictionStart, PredictionEnd,
                     BinSize, Assembly, SqlDB, testing=False):
    if testing:
        CoolDirID = os.path.join(CoolDir, UnitID, SampleName)
        if not os.path.exists(os.path.join(CoolDir, UnitID)):
            os.mkdir(os.path.join(CoolDir, UnitID))
    else:
        CoolDirID = os.path.join(CoolDir, UnitID)
    if not os.path.exists(CoolDirID):
        os.mkdir(CoolDirID)

    FileNamesOutput = {
        "Exp": os.path.join(CoolDirID, f"{UnitID}-Exp.cool"),
    }

    FileNamesBedgraphOutput = {
        "Exp": os.path.join(CoolDirID, f"{UnitID}-Exp.bedgraph"),
        "Pred": os.path.join(CoolDirID, f"{UnitID}-Pred.begraph"),
    }
    # Create ts
    SubmissionDate = datetime.datetime.now(datetime.timezone.utc).isoformat()

    # Create data struct
    Data = {}

    # Create temp files
    with Timer(f"Temp files created") as _:
        ExpCoolFiles = {}
        if not testing:
            TempDir = tempfile.TemporaryDirectory()
            for Type in FileNamesInput.keys():
                if Type == "Exp":
                    ExpCoolFiles[Type] = os.path.join(TempDir.name, f"{Type}.cool")
        else:
            for Type in FileNamesInput.keys():
                if Type == "Exp":
                    ExpCoolFiles[Type] = os.path.join(CoolDirID, f"{Type}.cool")

        for Type, FN in FileNamesInput.items():
            if Type == "Exp": Cool2Cool(FN, ExpCoolFiles[Type], Chrom)

        CoolInsFiles = {}
        for Type, FN in FileNamesInput.items():
            if Type == "Exp":
                CoolInsFiles[Type] = cooler.Cooler(FN)
            elif Type == "Pred":
                CoolInsFiles[Type] = FN

    # METRICS

    # Insulation
    with Timer(f"Insulation Dataset") as _:
        InsDataset = InsulationData(CoolInsFiles, Window=BinSize * 5, prediction_start=PredictionStart,
                                    prediction_end=PredictionEnd)

    with Timer(f"Insulation Score Pearson") as _:
        Data["Metrics.InsulationScorePearson"] = PearsonCorr(InsDataset["sum_balanced_Exp"],
                                                                InsDataset["sum_balanced_Pred"], method='spearman')

    # save insulatory score bedgraphs
    with Timer(f"Save Bedgraphs") as _:
        for Key in FileNamesBedgraphOutput.keys(): MakeBedgraph(
            ID=os.path.splitext(os.path.basename(FileNamesBedgraphOutput[Key]))[0],
            InsDataset=InsDataset[["chrom", "start", "end", "sum_balanced_" + Key]],
            OutputBedgraph=FileNamesBedgraphOutput[Key],
            Assembly=Assembly,
            Chrom=Chrom,
            DockerTmp="/home/fairwind/tmp")

    with Timer(f"Save MCOOLs") as _:
        for Key in ExpCoolFiles.keys():
            if Key == "Exp":
                MakeMcool(ID=os.path.splitext(os.path.basename(FileNamesOutput[Key]))[0],
                          InputCool=CoolInsFiles[Key].store,
                          OutputMcool=FileNamesOutput[Key], Resolution=BinSize,
                          DockerTmp="/home/fairwind/tmp")

    # SAVE
    with Timer(f"SQL") as _:
        try:
            sqlite_connection = sqlite3.connect(SqlDB)
            cursor = sqlite_connection.cursor()
            logging.info("DB Connected")

            AllMetricsSQL = json.dumps(Data, ensure_ascii=False)
            sqlite_select_query = f"update bm_metrics_wg_inss set Status='0', [Data.JSON]='{AllMetricsSQL}' where ID='{UnitID}';"
            cursor.execute(sqlite_select_query)
            sqlite_connection.commit()
            cursor.close()
        except sqlite3.Error as error:
            logging.info("SQLite3 Error")
            raise error
        finally:
            if (sqlite_connection):
                sqlite_connection.close()
                logging.info("DB Closed")


# ------======| MAIN |======------

def Main():
    Parser = CreateParser()
    Namespace = Parser.parse_args(sys.argv[1:])

    ConfigureLogger(Namespace.log)

    # Load Main Table
    MainTable = pandas.read_csv(Namespace.table, sep='\t', dtype=str).set_index("genome_locus_name").rename_axis(None,
                                                                                                                axis=0).transpose().to_dict()
    try:
        SampleData = MainTable[Namespace.sample]
    except KeyError:
        raise ValueError(f"Unknown Sample Name: '{Namespace.sample}'")

    FileNamesInput = {
        "Exp": os.path.join(SampleData["path_to_processed_hic_data_AlenaServer"], f"inter_{int(Namespace.resolution / 1000)}kb.cool"),
        "Pred": Namespace.prediction,
    }

    CreateDataFiles(
        UnitID=Namespace.id,
        AuthorName=Namespace.author,
        ModelName=Namespace.model,
        SampleName=Namespace.sample,
        FileNamesInput=FileNamesInput,
        CoolDir=Namespace.cooldir,
        Chrom=SampleData["locus_chr"],
        PredictionStart=int(SampleData["locus_start"]),
        PredictionEnd=int(SampleData["locus_end"]),
        BinSize=Namespace.resolution,
        Assembly=SampleData["genome_assembly"],
        SqlDB=Namespace.db)


if __name__ == "__main__": Main()
