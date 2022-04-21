__author__ = "Polina Belokopytova & Emil Viesná"
__version__ = "0.1b"
__date__ = "2021 Nov 1"

from contextlib import contextmanager
from cooltools.api import insulation
from cooltools.api import eigdecomp
from hicreppy import hicrep
from multiprocessing import cpu_count
from sklearn.metrics import precision_recall_curve, auc
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
import pandas as pd
import numpy as np
import requests

warnings.simplefilter(action='ignore', category=FutureWarning)
pandarallel.pandarallel.initialize(nb_workers=cpu_count(), verbose=0)

# ------======| CONST |======------

C_SCC_H = 2
C_CONTACT_COEF = 10000

## ------======| LOGGING |======------

def ExceptionHook(Type, Value, Traceback): logging.exception(f"{Type.__name__}: {Value}",
                                                             exc_info=(Type, Value, Traceback))


def ConfigureLogger(LogFileName: str = os.devnull, Level: int = logging.INFO) -> None:
    Formatter = "%(asctime)-30s%(levelname)-13s%(funcName)-35s%(message)s"
    logger = logging.getLogger()
    while logger.hasHandlers(): logger.removeHandler(logger.handlers[0])
    Handlers = [logging.FileHandler(LogFileName), logging.StreamHandler(sys.stderr)]
    logging.basicConfig(level=Level, format=Formatter, handlers=Handlers)
    sys.excepthook = ExceptionHook


def SecToTime(Sec: float) -> str: return f"{int(Sec / 3600):02}:{int((Sec // 60) % 60):02}:{int(Sec % 60):02}"


@contextmanager
def Timer(EndMessage: str, StartMessage: str = None):
    if StartMessage is not None: logging.info(StartMessage)
    StartTime = time.time()
    yield
    logging.info(f"{EndMessage} [{SecToTime(time.time() - StartTime)}]")


# ------======| BASH |======------

def SimpleSubprocess(Name, Command, CheckPipefail=False, Env=None, AllowedCodes=[]) -> None:
    # Timestamp
    with Timer(Name) as _:

        # Compose command
        Command = (f"source {Env}; " if Env is not None else f"") + (
            f"set -o pipefail; " if CheckPipefail else f"") + Command
        logging.debug(Command)

        # Shell
        Shell = subprocess.Popen(Command, shell=True, executable="/bin/bash", stdout=subprocess.PIPE,
                                 stderr=subprocess.PIPE)
        Stdout, Stderr = Shell.communicate()
        if (Shell.returncode != 0) and (Shell.returncode not in AllowedCodes): raise OSError(
            f"Command '{Name}' has returned non-zero exit code [{str(Shell.returncode)}]\nDetails: {Stderr.decode('utf-8')}")
        if Shell.returncode in AllowedCodes: logging.warning(
            f"Command '{Name}' has returned ALLOWED non-zero exit code [{str(Shell.returncode)}]")

    # Return
    return Stdout[:-1]


# ------======| DATA LOADING |======------

def BinSearch(Chrom, End, BinDict):
    try:
        return BinDict[(Chrom, End)]
    except KeyError:
        raise RuntimeError(f"Unknown bin")


def Tsv2Cool(TsvFN, OutputCoolFN, TemplateCoolFN, Chrom, PredictionStart, PredictionEnd, BinSize):
    for Line in [f"Input TSV: {TsvFN}", f"Output COOL: {OutputCoolFN}", f"Template COOL: {TemplateCoolFN}",
                 f"Chrom: {Chrom}", f"Resolution: {int(BinSize / 1000)} kb"]: logging.info(Line)
    DType = {"chrom": str, "end1": int, "end2": int, "balanced": float}
    Pixels = pandas.read_csv(TsvFN, sep='\t', names=DType.keys(), dtype=DType, header=None)
    #delete 2 first diagonals
    Pixels["distance"] = abs(Pixels["end2"] - Pixels["end1"])
    Pixels = Pixels[Pixels["distance"]>BinSize*2]
    #delete all contacts out of expected predicted region
    Pixels = Pixels[(Pixels["end2"]>=PredictionStart) & (Pixels["end2"]<=PredictionEnd)]
    Bins = cooler.Cooler(TemplateCoolFN).bins().fetch(Chrom)
    Bins["weight"] = 1 / C_CONTACT_COEF
    Bins = Bins.reset_index(drop=True)
    BinsDict = {(Line["chrom"], Line["end"]): index for index, Line in Bins.iterrows()}
    for num in [1, 2]: Pixels[f"bin{num}_id"] = Pixels.parallel_apply(
        lambda x: BinSearch(x["chrom"], x[f"end{num}"], BinsDict), axis=1)
    Pixels.dropna(inplace=True)
    Pixels["count"] = Pixels["balanced"] * pow(C_CONTACT_COEF, 2)
    Pixels = Pixels[["bin1_id", "bin2_id", "count"]]
    # check that all values are more than 1 and cooler won't delete it in new cool file
    if len(Pixels) - len(Pixels[Pixels["count"] > 1]) >=100:
        logging.warning(f"more than 100 contacts in cool file {OutputCoolFN} are equal to zero after normalization")
        assert len(Pixels) - len(Pixels[Pixels["count"] > 1]) >=100
    cooler.create_cooler(OutputCoolFN, Bins, Pixels)


def Cool2Cool(InputCoolFN, OutputCoolFN, Chrom):
    for Line in [f"Input COOL: {InputCoolFN}", f"Output COOL: {OutputCoolFN}", f"Chrom: {Chrom}"]: logging.info(Line)
    with tempfile.TemporaryDirectory() as TempDir:
        if InputCoolFN[:4] == "http":
            TempFile = os.path.join(TempDir, "input.cool")
            response = requests.get(InputCoolFN, stream=True)
            with open(TempFile, "wb") as handle: [handle.write(data) for data in response.iter_content()]
            Data = cooler.Cooler(TempFile)
        else:
            Data = cooler.Cooler(InputCoolFN)
        Bins = Data.bins().fetch(Chrom)
        BinDict = {item: index for index, item in enumerate(Bins.index.to_list())}
        Bins = Bins.reset_index()
        Pixels = Data.matrix(as_pixels=True, balance=True).fetch(Chrom)
        for col in ["bin1_id", "bin2_id"]: Pixels[col] = Pixels[col].apply(lambda x: BinDict[x])
        Pixels["count"] = Pixels["balanced"] * pow(C_CONTACT_COEF, 2)
        Pixels.dropna(inplace=True)
        Pixels = Pixels[Pixels["count"] != 0]
        # check that all values are more than 1 and cooler won't delete it in new cool file
        assert len(Pixels[Pixels["count"] > 1]) == len(Pixels)
        Bins["weight"] = 1 / C_CONTACT_COEF
        cooler.create_cooler(OutputCoolFN, Bins, Pixels)


def AlignCools(InputFNA, InputFNB, OutputFNA, OutputFNB, Chrom):
    for Line in [f"Input COOL [A]: {InputFNA}", f"Output COOL [A]: {OutputFNA}", f"Input COOL [B]: {InputFNB}",
                 f"Output COOL [B]: {OutputFNB}", f"Chrom: {Chrom}"]: logging.info(Line)
    InputA, InputB = cooler.Cooler(InputFNA), cooler.Cooler(InputFNB)
    BinsA, BinsB = InputA.bins().fetch(Chrom), InputB.bins().fetch(Chrom)
    PixelsA, PixelsB = InputA.matrix(as_pixels=True, balance=False).fetch(Chrom), InputB.matrix(as_pixels=True,
                                                                                                balance=False).fetch(
        Chrom)
    PixelsA.dropna(inplace=True)
    PixelsB.dropna(inplace=True)
    MergePixels = pandas.merge(PixelsA, PixelsB, how="inner", on=["bin1_id", "bin2_id"])
    PixelsA, PixelsB = MergePixels[["bin1_id", "bin2_id", "count_x"]].rename(columns={"count_x": "count"}), \
                       MergePixels[["bin1_id", "bin2_id", "count_y"]].rename(columns={"count_y": "count"})
    cooler.create_cooler(OutputFNA, BinsA, PixelsA)
    cooler.create_cooler(OutputFNB, BinsB, PixelsB)


def GetMatrix(Cool, Chrom): return Cool.matrix(as_pixels=True, balance=True).fetch(Chrom)


def InsulationData(Datasets, Window, region_start, region_end):
    for Line in [f"Window: {Window}"]: logging.info(Line)
    InsScores = {
        Type: insulation.calculate_insulation_score(CoolData, [Window], ignore_diags=2, append_raw_scores=True).rename(
            columns={f"sum_balanced_{Window}": f"sum_balanced_{Type}"}) for Type, CoolData in Datasets.items()}
    Result = None
    for Type, Data in InsScores.items(): Result = Data.copy() if Result is None else pandas.merge(Result, Data,
                                                                                                  how="inner",
                                                                                                  on=["chrom", "start",
                                                                                                      "end"])
    Result = Result[["chrom", "start", "end"] + [f"sum_balanced_{Type}" for Type in InsScores.keys()]]
    Result = Result.query("start >= @region_start & end <= @region_end")
    return Result

def Calculate_compartment_score(PrefixName, CoolFile):
    SimpleSubprocess(Name="CooltoolsCall-compartments",
                     Command=f"cooltools eigs-cis --n-eigs 1 -o \"{PrefixName}\" \"{CoolFile}\"")

def Create_compartment_partition_and_input(CompScoreFilePath, CoolFile, OutFolder, Chrom,Type, Resolution):
    comp_score = pd.read_csv(CompScoreFilePath, sep="\t")
    cool_data = cooler.Cooler(CoolFile).matrix(as_pixels=True, balance=True, join=True).fetch(Chrom)
    count_data = pd.DataFrame(data={'chr': cool_data["chrom1"], 'contact_st': cool_data["end1"],
                                    'contact_en': cool_data["end2"], 'contact_count': cool_data["count"],
                                    'balanced': cool_data["balanced"]})
    min_bin = min(min(count_data["contact_st"]), min(count_data["contact_en"]))
    max_bin = max(max(count_data["contact_st"]), max(count_data["contact_en"]))
    max_bin_name = int((max_bin - min_bin)/Resolution)
    comp_score["bin_name"] = comp_score["end"].apply(lambda x: int((x-min_bin)/Resolution))
    comp_score["comp"] = comp_score["E1"].apply(lambda x: "A" if x>0 else "B")
    comp_score["masked"] = comp_score["E1"].isnull()
    comp_score["masked"] = comp_score["masked"].apply(lambda x: "MASKED" if x else np.nan)
    comp_score = comp_score[(comp_score.bin_name >=0) & (comp_score.bin_name <= max_bin_name)]
    if Type == "Exp":
        comp_score[["bin_name", "comp", "masked"]].to_csv(OutFolder+"compartment_partition.txt", sep=" ", index=False, header=False)
    merge_data = pd.merge(count_data, comp_score, how="inner", left_on=["contact_st"], right_on=["end"])
    merge2_data = pd.merge(merge_data, comp_score, how="inner", left_on=["contact_en"], right_on=["end"])
    merge2_data["comp_type"] = merge2_data[["bin_name_x", "bin_name_y"]].apply(lambda x: "same" if x[0]==x[1] else "else", axis=1)
    merge2_data[["bin_name_x", "bin_name_y", "balanced", "comp_type"]].to_csv(OutFolder+"input_matrix_"+Type+".tab", sep=" ", index=False, header=False)

def calculate_compartment_strength_and_Ps(Type, OutFolder):
    print("calculate for")
    nbins = len(open(OutFolder+"compartment_partition.txt").readlines())
    SimpleSubprocess(Name="compartmentScore_and_Ps_calculation",
                     Command=f"bash scripts_for_comp_strength/compartmentScore_and_Ps_calculation.sh \"{str(nbins)}\" \"{Type}\" \"{OutFolder}\"")

def Calculate_compartment_strength_and_Ps(Datasets, Chrom, Resolution, OutFolder):
    # Calculate E1 vector for Exp and Pred and save it in file
    [Calculate_compartment_score(PrefixName=OutFolder+"/"+Type, CoolFile=CoolFile) for Type, CoolFile in Datasets.items()]
    # create input files with compartment partition for Marco script
    [Create_compartment_partition_and_input(CompScoreFilePath=OutFolder+"/"+Type+".cis.vecs.tsv", CoolFile=CoolFile, OutFolder=OutFolder,
                                            Chrom=Chrom, Type=Type, Resolution=Resolution) for Type, CoolFile in Datasets.items()]
    #calcilate compartment strength and P(s) using Marco's script
    [calculate_compartment_strength_and_Ps(Type=Type, OutFolder=OutFolder) for Type, CoolFile in Datasets.items()]

def Comp_score_corr(control_data_file, predicted_data_file):
    control_comp_strength = pd.read_csv(control_data_file, sep=" ", names=["bin", "comp_type", "comp_strength"])
    predicted_comp_strength = pd.read_csv(predicted_data_file, sep=" ", names=["bin", "comp_type", "comp_strength"])
    assert len(control_comp_strength) == len(predicted_comp_strength)
    #delete comp strength == -1 (if sum of obs exp values in compartments for bin equals to 0 )
    control_comp_strength = control_comp_strength[control_comp_strength["comp_strength"]>0]
    predicted_comp_strength = predicted_comp_strength[predicted_comp_strength["comp_strength"] > 0]
    merge_data = pd.merge(control_comp_strength, predicted_comp_strength, how="inner", on=["bin", "comp_type"])
    pearson_corr = merge_data["comp_strength_x"].corr(merge_data["comp_strength_y"], method="spearman")
    return pearson_corr

def Ps_corr(control_data_file, predicted_data_file):
    control_Ps = pd.read_csv(control_data_file, sep=" ", names=["bin", "average_contact"])
    predicted_Ps = pd.read_csv(predicted_data_file, sep=" ", names=["bin", "average_contact"])
    merge_data = pd.merge(control_Ps, predicted_Ps, how="inner", on=["bin"])
    assert len(merge_data)==len(control_Ps)==len(predicted_Ps)
    pearson_corr = merge_data["average_contact_x"].corr(merge_data["average_contact_y"], method="spearman")
    return pearson_corr

def MakeMcool(ID, InputCool, OutputMcool, Resolution, DockerTmp):
    for Line in [f"Input COOL: {InputCool}", f"Output MCOOL: {OutputMcool}", f"Resolution: {int(Resolution / 1000)} kb"]: logging.info(Line)
    with tempfile.TemporaryDirectory() as TempDir:
        TempFile = os.path.join(TempDir, "temp.cool")
        SimpleSubprocess(Name = "CoolerZoomify", Command = f"cooler zoomify -n 8 -r {Resolution}N --balance -o \"{TempFile}\" \"{InputCool}\"")
        SimpleSubprocess(Name = "Copy2DockerTmp", Command = f"cp \"{TempFile}\" \"{os.path.join(DockerTmp, 'bm_temp.cool')}\"")
        SimpleSubprocess(Name = "HiGlassIngest", Command = f"docker exec higlass-container python higlass-server/manage.py ingest_tileset --filename \"{os.path.join('/tmp', 'bm_temp.cool')}\" --filetype cooler --datatype matrix --uid \"{ID}\" --project-name \"3DGenBench\" --name \"{ID}\" --coordSystem \"{ID}-CoordSystem\"")
        SimpleSubprocess(Name = "Copy2MCoolDir", Command = f"cp \"{TempFile}\" \"{OutputMcool}\"")

def MakeBedgraph(ID, InsDataset, OutputBedgraph, Assembly, Chrom, DockerTmp, testing=False):
    for Line in [f"Output Bedgraph: {OutputBedgraph}"]: logging.info(Line)
    with tempfile.TemporaryDirectory() as TempDir:
        TempFile = os.path.join(TempDir, "temp.bedgraph")
        if not testing:
            InsDataset.to_csv(TempFile, sep="\t", index=False, header=False)
        else:
            InsDataset.to_csv(OutputBedgraph, sep="\t", index=False, header=False)
        if not testing:
            SimpleSubprocess(Name="Copy2DockerTmp",
                        Command=f"cp \"{TempFile}\" \"{os.path.join(DockerTmp, 'bm_temp.bedgraph')}\"")
            SimpleSubprocess(Name="ChromSizes",
                        Command=f"grep -P '{Chrom}\\t' ./chrom.sizes/{Assembly}.chrom.sizes > {os.path.join(DockerTmp, 'chrom.size')}")
            SimpleSubprocess(Name="Bedgraph2BigWig",
                        Command=f"bedGraphToBigWig \"{TempFile}\" \"{os.path.join(DockerTmp, 'chrom.size')}\" \"{os.path.join(DockerTmp, 'bm_temp.bigwig')}\"")
            SimpleSubprocess(Name="HiGlassIngestCoords",
                            Command=f"docker exec higlass-container python higlass-server/manage.py ingest_tileset --filename /tmp/chrom.size --filetype chromsizes-tsv --datatype chromsizes --coordSystem \"{ID}-CoordSystem\" --uid \"{ID}-Coord\" --project-name \"3DGenBench\" --name \"{ID}-Coord\"")
            SimpleSubprocess(Name="HiGlassIngest",
                            Command=f"docker exec higlass-container python higlass-server/manage.py ingest_tileset --filename /tmp/bm_temp.bigwig --filetype bigwig --datatype vector --uid \"{ID}-InsHitile\" --project-name \"3DGenBench\" --name \"{ID}--InsHitile\" --coordSystem \"{ID}-CoordSystem\"")
            SimpleSubprocess(Name="Copy2MCoolDir", Command=f"cp \"{TempFile}\" \"{OutputBedgraph}\"")


# ------======| METRICS |======------

def PearsonCorr(SeriesA, SeriesB, method="pearson"): return SeriesA.corr(SeriesB, method=method)


def SCC(CoolA, CoolB, region_start, region_end, h):
    #TODO how to choose h, what is the impact of 1st diagonal
    region_size = region_end-region_start
    MaxDist_inRegion = region_size - (int(region_size/5))
    MaxDist = 1000000 if MaxDist_inRegion>1000000 else MaxDist_inRegion
    return hicrep.genome_scc(CoolA, CoolB, max_dist=MaxDist, h=h)


# ------======| DRAFT VISUALIZATION |======------

def VisualizeCool(InputCool, OutputPng, Region):
    SimpleSubprocess(Name="VisualizeCool",
                     Command=f"cooler show --out \"{OutputPng}\" -b --dpi 150 \"{InputCool}\" {Region}")


def VisualizePR(PRData, Name, FN):
    Precision, Recall = json.loads(PRData["Precision"]), json.loads(PRData["Recall"])
    fig, ax = plt.subplots(figsize=(6, 6), dpi=150)
    ax.plot(Recall, Precision)
    ax.set(xlabel="Recall", ylabel="Precision", title=f"{Name} PR Curve\nAUC = {PRData['AUC']:.10f}")
    ax.grid()
    fig.savefig(FN)
    plt.clf()


def VisualizeRandom(RandomData, FN):
    Random, Real = json.loads(RandomData["Random"]), int(RandomData["Real"])
    fig, ax = plt.subplots(figsize=(8, 6), dpi=150)
    ax.set(title=f"Real vs Random Ectopic Intersections")
    ax.hist(Random, bins=200, histtype='step')
    ax.axvline(x=Real, color="red")
    fig.savefig(FN)
    plt.clf()



def VisualizeEctopicArray(EctopicArray, FN):
    Data = json.loads(EctopicArray)
    fig, ax = plt.subplots(figsize=(6, 6), dpi=150)
    ax.set(title=f"Ectopic Interactions")
    pos = ax.matshow(Data, cmap="bwr", vmin=-8, vmax=8)
    fig.colorbar(pos, ax=ax)
    fig.savefig(FN)
    plt.clf()


# ------======| HASH |======------

def HashJSON(Object, Key): return hashlib.sha256(
    (json.dumps(Object, ensure_ascii=False) + Key).encode('utf8')).hexdigest()


def CheckHash(Object, key):
    Object = copy.deepcopy(Object)
    ObjectHash = copy.deepcopy(Object["__hash__"])
    del Object["__hash__"]
    return HashJSON(Object, key) == ObjectHash


# ------======| PARSER |======------

def CreateParser():
    Parser = argparse.ArgumentParser(formatter_class=argparse.RawDescriptionHelpFormatter,
                                     description=f"BenchmarkPipeline by {__author__}", epilog=f"Email: regnveig@ya.ru")
    Parser.add_argument('--version', action='version', version=__version__)

    Parser.add_argument('-i', '--id', required=True, type=str, help=f"Unit ID")
    Parser.add_argument('-a', '--author', required=True, type=str, help=f"Author ID")
    Parser.add_argument('-m', '--model', required=True, type=str, help=f"Model name")
    Parser.add_argument('-s', '--sample', required=True, type=str, help=f"Sample name")
    Parser.add_argument('-r', '--resolution', required=True, type=int, help=f"Resolution")
    Parser.add_argument('-t', '--table', required=True, type=str, help=f"Main table path")
    Parser.add_argument('-d', '--db', required=True, type=str, help=f"SQLite DB path")
    Parser.add_argument('-c', '--cooldir', required=True, type=str, help=f"COOL root dir")
    Parser.add_argument('-l', '--log', required=True, type=str, help=f"Log file")
    Parser.add_argument('-P', '--prediction', required=True, type=str, help=f"prediction TSV path")

    return Parser


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
        "Pred": os.path.join(CoolDirID, f"{UnitID}-Pred.cool"),
    }

    FileNamesBedgraphOutput = {
        "Exp": os.path.join(CoolDirID, f"{UnitID}-Exp.bedgraph"),
        "Pred": os.path.join(CoolDirID, f"{UnitID}-Pred.begraph"),
    }
    # Create ts
    SubmissionDate = datetime.datetime.now(datetime.timezone.utc).isoformat()

    # Create data struct
    Data = {}

    # Create temp cool files
    with Timer(f"Temp files created") as _:
        if not testing:
            TempDir = tempfile.TemporaryDirectory()
            TempFiles = {Type: os.path.join(TempDir.name, f"{Type}.cool") for Type in FileNamesInput.keys()}
        else:
            TempFiles = {Type: os.path.join(CoolDirID, f"{Type}.cool") for Type in FileNamesInput.keys()}
        for Type, FN in FileNamesInput.items():
            if Type == "Exp": Cool2Cool(FN, TempFiles[Type], Chrom)
            if Type == "Pred": Tsv2Cool(FN, TempFiles[Type], TempFiles["Exp"], Chrom, PredictionStart, PredictionEnd, BinSize)
    # Align
    with Timer(f"Sample type align") as _:
        if not testing:
            SampleTypeAligned = {Type: os.path.join(TempDir.name, f"{Type}-SampleTypeAligned.cool") for Type in
                                 FileNamesInput.keys()}
        else:
            SampleTypeAligned = {Type: os.path.join(CoolDirID, f"{Type}-SampleTypeAligned.cool") for Type in
                                 FileNamesInput.keys()}
        AlignCools(TempFiles["Exp"], TempFiles["Pred"],SampleTypeAligned["Exp"], SampleTypeAligned["Pred"], Chrom)
        SampleTypeAligned = {Type: cooler.Cooler(FN) for Type, FN in SampleTypeAligned.items()}
        # DRAFT
        for Type, Cool in SampleTypeAligned.items():
            VisualizeCool(InputCool=Cool.store,
                          OutputPng=os.path.join(CoolDirID, f".{UnitID}-{Type}SampleTypeAligned.png"),
                          Region=f"{Chrom}:{PredictionStart}-{PredictionEnd}")
    # METRICS
    # Pearson
    with Timer(f"Pearson") as _:
        Data["Metrics.Pearson"] = PearsonCorr(GetMatrix(SampleTypeAligned["Exp"], Chrom)["balanced"],
                                                 GetMatrix(SampleTypeAligned["Pred"], Chrom)["balanced"], method="spearman")
    # SCC
    with Timer(f"SCC") as _:
        Data["Metrics.SCC"] = SCC(SampleTypeAligned["Exp"], SampleTypeAligned["Pred"],
                                     region_start=PredictionStart, region_end=PredictionEnd, h=C_SCC_H)

    # Insulation
    with Timer(f"Insulation Dataset") as _:
        InsDataset = InsulationData(SampleTypeAligned, Window=BinSize * 5, region_start=PredictionStart,
                                    region_end=PredictionEnd)
        print(InsDataset)

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
    # Compartment score
    with Timer(f"Compartment strength") as _:
        Datasets = {Type: os.path.join(TempDir.name, f"{Type}-SampleTypeAligned.cool") for Type in FileNamesInput.keys()}
        Calculate_compartment_strength_and_Ps(OutFolder=CoolDirID+"/", Datasets=Datasets, Chrom =Chrom, Resolution=BinSize)
        # Pearson corr of compartment strength
        Data["Metrics.CompartmentStrengthPearson"] = Comp_score_corr(control_data_file=CoolDirID + "/compartment_strength_per_bin_Exp.txt",
                                          predicted_data_file=CoolDirID + "/compartment_strength_per_bin_Pred.txt")
        # Pearson corr of P(s)
        Data["Metrics.PsPearson"] = Ps_corr(control_data_file=CoolDirID + "/average_number_of_contacts_vs_gendist_matrix_Exp.txt",
                          predicted_data_file=CoolDirID + "/average_number_of_contacts_vs_gendist_matrix_Pred.txt")
    with Timer(f"Save MCOOLs") as _:
        for Key in SampleTypeAligned.keys(): MakeMcool(ID=os.path.splitext(os.path.basename(FileNamesOutput[Key]))[0],
                                                       InputCool=SampleTypeAligned[Key].store,
                                                       OutputMcool=FileNamesOutput[Key], Resolution=BinSize,
                                                       DockerTmp="/home/fairwind/tmp")

    # SAVE
    with Timer(f"SQL") as _:
        try:
            sqlite_connection = sqlite3.connect(SqlDB)
            cursor = sqlite_connection.cursor()
            logging.info("DB Connected")

            AllMetricsSQL = json.dumps(Data, ensure_ascii=False)
            sqlite_select_query = f"update bm_metrics_wg set Status='0', [Data.JSON]='{AllMetricsSQL}' where ID='{UnitID}';"
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
