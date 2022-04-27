__author__ = "Minja Fishman"
__version__ = "0.01a"

from contextlib import contextmanager
import argparse
import datetime
import json
import logging
import numpy
import os, sys
import pandas, pandarallel
import subprocess
import sqlite3
import tempfile
import time
import warnings
from multiprocessing import cpu_count

warnings.simplefilter(action='ignore', category=FutureWarning)
pandarallel.pandarallel.initialize(nb_workers = cpu_count(), verbose = 0)


## ------======| LOGGING |======------

def ExceptionHook(Type, Value, Traceback): logging.exception(f"{Type.__name__}: {Value}", exc_info=(Type, Value, Traceback))

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
	
# ------======| PARSER |======------

def CreateParser():
	Parser = argparse.ArgumentParser(formatter_class=argparse.RawDescriptionHelpFormatter, description=f"BenchmarkPipeline by {__author__}", epilog=f"Email: regnveig@ya.ru")
	Parser.add_argument('--version', action='version', version=__version__)

	Parser.add_argument('-i', '--id', required=True, type=str, help=f"Unit ID")
	Parser.add_argument('-a', '--author', required=True, type=str, help=f"Author ID")
	Parser.add_argument('-m', '--model', required=True, type=str, help=f"Model name")
	Parser.add_argument('-s', '--sample', required=True, type=str, help=f"Sample name")
	Parser.add_argument('-t', '--table', required=True, type=str, help=f"Main table path")
	Parser.add_argument('-p', '--predicted', required=True, type=str, help=f"Predicted data")
	Parser.add_argument('-d', '--db', required=True, type=str, help=f"SQLite DB path")
	Parser.add_argument('-l', '--log', required=True, type=str, help=f"Log file")
	
	return Parser

def _find_best_MSE(record, experimet_df):
	ids = record["both_side_overlaping_region_ids"]
	if len(ids) == 0:
		return numpy.NaN
	predicted_count = record["count"]
	return ((experimet_df.loc[ids,"count"].values - numpy.array([predicted_count]*len(ids)))**2).mean()


def _core_ChIA_PET_Benchmark(predicted_data_path, reference_data_path):
	#  Main function to compute ChIA-PET benchmark metrics
	# accepts two input files: reference and predicted ChIA-PET datasets
	# returns a dictionary {'metric_name':metric_value}
	
	# ------------- read data and check qoulity ----------------
	experiment = pandas.read_csv(reference_data_path, header=None, sep="\t", names=["chrom1","start1","end1","chrom2","start2","end2","count"])
	# TODO: refactor rand1 to "predicted"
	rand1 = pandas.read_csv(predicted_data_path, header=None, sep="\t", names=["chrom1","start1","end1","chrom2","start2","end2","count"])
	
	assert numpy.all(numpy.all(numpy.equal(rand1["chrom1"].values,rand1["chrom2"].values))), "only cis-interactions are allowed for ChIA-PET data"
	assert len(rand1)>0, "No interactions in ChIA-PET data"

	# ------------- intersect experimental and predicted data ----------------
	rand1["intervals_1"] = pandas.IntervalIndex.from_arrays(rand1["start1"],rand1["end1"])
	rand1["intervals_2"] = pandas.IntervalIndex.from_arrays(rand1["start2"],rand1["end2"])
	rand1["both_side_overlaping_region_ids"] = numpy.NaN

	# naming conventions: g1 for prediction, g2 for reference
	g1 = rand1.groupby("chrom1") 
	g2 = experiment.groupby("chrom1")

	# Here we will iterate over chroms
	# and find those interval pairs of g1 that both in pair overlap 
	# with intervals of the same pair of g2

	for group_id in g1.groups: # iter over prediction chroms
		logging.info(f"Processing chromosome {group_id}") # TODO: remove this log it it's not requiered
		if not group_id in g2.groups: raise ValueError("Chromosome of the predicted dataset does not match reference data: ", group)
		group = g1.get_group(group_id) # get chrom data
		if len(group)<10: # note: if there is only 1 interval behaviour of above functions
						  # should be additionally tested
			raise ValueError("Too few data for comparison on chromosome: ", group)

		# construct interval object for reference data
		g2_intervals_1 = pandas.IntervalIndex.from_arrays(g2.get_group(group_id)["start1"].values, g2.get_group(group_id)["end1"].values)
		g2_intervals_2 = pandas.IntervalIndex.from_arrays(g2.get_group(group_id)["start2"].values, g2.get_group(group_id)["end2"].values)
		index_array = g2.get_group(group_id).index

		left_overlaping_region_ids = group["intervals_1"].apply(lambda x: index_array[g2_intervals_1.overlaps(x)])
		right_overlaping_region_ids = group["intervals_2"].apply(lambda x: index_array[g2_intervals_2.overlaps(x)])
		both_side_overlaping_region_ids = [numpy.intersect1d(i,j) for i,j in zip(left_overlaping_region_ids, right_overlaping_region_ids)]
		assert len(both_side_overlaping_region_ids) == len(group)
		rand1.loc[group.index,"both_side_overlaping_region_ids"] = both_side_overlaping_region_ids
		
	# ------------- compute metrics ----------------

	assert pandas.isna(rand1["both_side_overlaping_region_ids"]).sum() == 0

	TP = rand1["both_side_overlaping_region_ids"].apply(lambda x: 1 if len(x)>0 else 0).sum()
	FP = rand1["both_side_overlaping_region_ids"].apply(lambda x: 1 if len(x)==0 else 0).sum()
	assert (TP+FP)==len(rand1)

	unique_found_ids = len(numpy.intersect1d(experiment.index, numpy.concatenate(rand1["both_side_overlaping_region_ids"].values)))
	FN = len(experiment.index) - unique_found_ids
	
	logging.info("Computing MSE") # TODO: remove this log it it's not requiered
	best_MSE = rand1.apply(_find_best_MSE, experimet_df = experiment, axis="columns")
	results = {
		"True positive, fraction of predicted interactions" : TP / len(rand1),
		"False positive, fraction of predicted interactions:" : FP / len(rand1),
		"False negative fraction, fraction of experimental interactions:" : FN / len(experiment),
		"PET MSE for overlaping interactions:" : numpy.nanmean(best_MSE.values),
	}
	return results

def Main():
	
	Parser = CreateParser()
	Namespace = Parser.parse_args(sys.argv[1:])
	
	ConfigureLogger(Namespace.log)
	
	# Load Main Table
	MainTable = pandas.read_csv(Namespace.table, sep='\t', dtype=str).set_index("genome_locus_name").rename_axis(None, axis=0).transpose().to_dict()
	try:
		SampleData = MainTable[Namespace.sample]
	except KeyError:
		raise ValueError(f"Unknown Sample Name: '{Namespace.sample}'")
	
	ReferencePath = os.path.join(SampleData["path_to_processed_hic_data_AlenaServer"], f"ChIA-PET.txt")
	PredictedPath = Namespace.predicted
	with Timer(f"ChIA-PET Benchmark") as _:
		Results = _core_ChIA_PET_Benchmark(predicted_data_path=PredictedPath, reference_data_path=ReferencePath)
	
	with Timer(f"SQL") as _:
		try:
			sqlite_connection = sqlite3.connect(Namespace.db)
			cursor = sqlite_connection.cursor()
			logging.info("DB Connected")
			AllMetricsSQL = json.dumps(Results, ensure_ascii=False)
			sqlite_select_query = f"update chia_pet set Status='0', [Data.JSON]='{AllMetricsSQL}' where ID='{Namespace.id}';"
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
				
if __name__ == "__main__": Main()
