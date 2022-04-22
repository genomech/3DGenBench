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

warnings.simplefilter(action='ignore', category=FutureWarning)
pandarallel.pandarallel.initialize(nb_workers = cpu_count(), verbose = 0)

# ------======| CONST |======------

C_SCC_MAXDIST = 1500000
C_SCC_H = 2
C_CONTACT_COEF = 10000
C_RANDOM_INTER_N = 5000
C_RANDOM_INTER_SIGMA = 2

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

# ------======| BASH |======------

def SimpleSubprocess(Name, Command, CheckPipefail = False, Env = None, AllowedCodes = []) -> None:
	
	# Timestamp
	with Timer(Name) as _:
	
		# Compose command
		Command = (f"source {Env}; " if Env is not None else f"") + (f"set -o pipefail; " if CheckPipefail else f"") + Command
		logging.debug(Command)
		
		# Shell
		Shell = subprocess.Popen(Command, shell=True, executable="/bin/bash", stdout=subprocess.PIPE, stderr=subprocess.PIPE)
		Stdout, Stderr = Shell.communicate()
		if (Shell.returncode != 0) and (Shell.returncode not in AllowedCodes): raise OSError(f"Command '{Name}' has returned non-zero exit code [{str(Shell.returncode)}]\nDetails: {Stderr.decode('utf-8')}")
		if Shell.returncode in AllowedCodes: logging.warning(f"Command '{Name}' has returned ALLOWED non-zero exit code [{str(Shell.returncode)}]")
	
	# Return
	return Stdout[:-1]

# ------======| DATA LOADING |======------

def BinSearch(Chrom, End, BinDict):
	try:
		return BinDict[(Chrom, End)]
	except KeyError:
		raise RuntimeError(f"Unknown bin")

def Tsv2Cool(TsvFN, OutputCoolFN, TemplateCoolFN, Chrom, BinSize):
	for Line in [f"Input TSV: {TsvFN}", f"Output COOL: {OutputCoolFN}", f"Template COOL: {TemplateCoolFN}",
				 f"Chrom: {Chrom}", f"Resolution: {int(BinSize / 1000)} kb"]: logging.info(Line)
	DType = {"chrom": str, "end1": int, "end2": int, "balanced": float}
	Pixels = pandas.read_csv(TsvFN, sep='\t', names=DType.keys(), dtype=DType, header=None)
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
	print(Pixels[Pixels["count"] < 1]["count"])
	print(len(Pixels[Pixels["count"] > 1]), len(Pixels))
	# assert len(Pixels[Pixels["count"] > 1]) == len(Pixels), f""
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
		# check that all values are more than 1 and cooler won't delete it in new cool file
		# assert len(Pixels[Pixels["count"] > 1]) == len(Pixels)
		Bins["weight"] = 1 / C_CONTACT_COEF
		cooler.create_cooler(OutputCoolFN, Bins, Pixels)

def AlignCools(InputFNA, InputFNB, OutputFNA, OutputFNB, Chrom, testing=False):
	for Line in [f"Input COOL [A]: {InputFNA}", f"Output COOL [A]: {OutputFNA}", f"Input COOL [B]: {InputFNB}",
				 f"Output COOL [B]: {OutputFNB}", f"Chrom: {Chrom}"]: logging.info(Line)
	InputA, InputB = cooler.Cooler(InputFNA), cooler.Cooler(InputFNB)
	BinsA, BinsB = InputA.bins().fetch(Chrom), InputB.bins().fetch(Chrom)
	PixelsA, PixelsB = InputA.matrix(as_pixels=True, balance=True).fetch(Chrom), InputB.matrix(as_pixels=True,balance=True).fetch(Chrom)
	PixelsA.dropna(inplace=True)
	MergePixels = pandas.merge(PixelsA, PixelsB, how="inner", on=["bin1_id", "bin2_id"])
	PixelsA, PixelsB = MergePixels[["bin1_id", "bin2_id", "count_x", 'balanced_x']].rename(columns={"count_x": "count","balanced_x": "balanced"}), \
					   MergePixels[["bin1_id", "bin2_id", "count_y", "balanced_y"]].rename(columns={"count_y": "count", "balanced_y": "balanced"})
	cooler.create_cooler(OutputFNA, BinsA, PixelsA)
	cooler.create_cooler(OutputFNB, BinsB, PixelsB)

def GetMatrix(Cool, Chrom): return Cool.matrix(as_pixels=True, balance=True).fetch(Chrom)

def InsulationData(Datasets, Window, capture_start, capture_end):
	for Line in [f"Window: {Window}"]: logging.info(Line)
	InsScores = {Type: insulation.calculate_insulation_score(Data, [Window], ignore_diags=2, append_raw_scores=True).rename(columns={f"sum_balanced_{Window}": f"sum_balanced_{'-'.join(Type)}"}) for Type, Data in Datasets.items()}
	Result = None
	for Type, Data in InsScores.items(): Result = Data.copy() if Result is None else pandas.merge(Result, Data, how="inner", on=["chrom", "start", "end"])
	Result = Result[["chrom", "start", "end"] + [f"sum_balanced_{'-'.join(Type)}" for Type in InsScores.keys()]]
	Result = Result.query("start >= @capture_start & end <= @capture_end")
	for DT in ["Exp", "Pred"]: 
		Result[f"sum_balanced_Mut/Wt-{DT}"] = Result.apply(lambda x: 0 if x[f"sum_balanced_Wt-{DT}"] == 0 else (x[f"sum_balanced_Mut-{DT}"] / x[f"sum_balanced_Wt-{DT}"]), axis=1)
		NonZero = Result[f"sum_balanced_Mut/Wt-{DT}"][Result[f"sum_balanced_Mut/Wt-{DT}"] != 0]
		Mean, Std = numpy.mean(NonZero), numpy.std(NonZero)
		NonZero = NonZero.apply(lambda x: (x - Mean) / Std)
		NonZero.name = f"sum_balanced_sigma_Mut/Wt-{DT}"
		Result = pandas.concat([Result, NonZero], axis=1)
	Result["Y-True"] = Result["sum_balanced_sigma_Mut/Wt-Exp"].apply(lambda x: (x == x) and ((x > 2) or (x < -2)))
	return Result

def EctopicInteractionsArray(CoolWT, CoolMut, Chrom, PredictionStart, PredictionEnd, SampleData):
	Prediction = f"{Chrom}:{PredictionStart}-{PredictionEnd}"
	for Line in [f"WT COOL: {CoolWT.store}", f"Mut COOL: {CoolMut.store}", f"Prediction region: {Prediction}"]: logging.info(Line)
	
	def PrepareData(Cool, for_coef=True):
		Data = numpy.nan_to_num(Cool.matrix(balance=True).fetch(Prediction))
		#fill all interactions of bins inside deletion by zeroes, don't do it with inversion or duplication
		for i,rearr_type in enumerate(SampleData["rearrangement_type"].split("_")):
			if for_coef:
				if rearr_type=="deletion" or rearr_type=="duplication":
					RearrStartBin = (int(SampleData["start"+str(i+1)]) - PredictionStart) // Cool.binsize
					RearrEndBin = (int(SampleData["end"+str(i+1)]) - PredictionStart) // Cool.binsize
					Data[RearrStartBin:RearrEndBin+1, :] = numpy.zeros(Data[RearrStartBin:RearrEndBin+1, :].shape)
					Data[:, RearrStartBin:RearrEndBin+1] = numpy.zeros(Data[:, RearrStartBin:RearrEndBin+1].shape)
			else:
				if rearr_type=="deletion":
					RearrStartBin = (int(SampleData["start"+str(i+1)]) - PredictionStart) // Cool.binsize
					RearrEndBin = (int(SampleData["end"+str(i+1)]) - PredictionStart) // Cool.binsize
					Data[RearrStartBin:RearrEndBin+1, :] = numpy.zeros(Data[RearrStartBin:RearrEndBin+1, :].shape)
					Data[:, RearrStartBin:RearrEndBin+1] = numpy.zeros(Data[:, RearrStartBin:RearrEndBin+1].shape)
		return Data
	
	#SumData = lambda Data: sum(list(map(sum, Data)))
	DataWT_for_coef, DataMut_for_coef = PrepareData(CoolWT, for_coef=True), PrepareData(CoolMut, for_coef=True)
	DataWT, DataMut = PrepareData(CoolWT, for_coef=False), PrepareData(CoolMut, for_coef=False)
	# # use only non zero contacts in WT and Mut
	# print("nonzero", numpy.count_nonzero(DataWT), numpy.count_nonzero(DataMut))
	# DataWT[DataMut==0] = 0
	# DataMut[DataWT == 0] = 0
	# print("nonzero", numpy.count_nonzero(DataWT), numpy.count_nonzero(DataMut))
	SumWT, SumMut = numpy.sum(DataWT_for_coef),  numpy.sum(DataMut_for_coef)
	# normalize mut and WT data by coverage
	DataMut = DataMut * (SumWT / SumMut)
	#normalize duplication region by coverage in te case of duplication
	for i, rearr_type in enumerate(SampleData["rearrangement_type"].split("_")):
		if rearr_type=="duplication":
			RearrStartBin = (int(SampleData["start" + str(i+1)]) - PredictionStart) // CoolWT.binsize
			RearrEndBin = (int(SampleData["end" + str(i+1)]) - PredictionStart) // CoolWT.binsize
			WT_Sum_dup_conacts =  numpy.sum(DataWT[RearrStartBin:RearrEndBin + 1, :RearrStartBin])+ numpy.sum(DataWT[RearrEndBin:, RearrStartBin:RearrEndBin + 1])
			Mut_Sum_dup_conacts =numpy.sum(DataMut[RearrStartBin:RearrEndBin + 1, :RearrStartBin])+ numpy.sum(DataMut[RearrEndBin:, RearrStartBin:RearrEndBin + 1])
			norm_coef = WT_Sum_dup_conacts/ Mut_Sum_dup_conacts
			DataMut[RearrStartBin:RearrEndBin + 1, :] *= norm_coef
			DataMut[:, RearrStartBin:RearrEndBin + 1] *= norm_coef
	# get dif array of normalized contacts
	DiffArray = DataMut - DataWT
	
	logging.info(f"Diff array prepared")
	# To take into account the genomic distance bias, we normalized
	# the difference matrix by dividing each sub-diagonal by the average wt reads count at its
	# corresponding pairwise genomic distance.
	for i in range(len(DiffArray)):
		X, Y = numpy.array(range(0, len(DiffArray) - i)), numpy.array(range(i, len(DiffArray)))
		Coeff = numpy.average(DataWT[X,Y])
		if Coeff != 0: DiffArray[X,Y], DiffArray[Y,X] = (DiffArray[X,Y] / Coeff), (DiffArray[X,Y] / Coeff)
	
	logging.info(f"Diff matrix normalized by genomic distance bias")
	#get list of contacts on diagonals
	DiffDiags = [(k, numpy.diag(DiffArray, k=k)) for k in range(len(DiffArray))]
	numpy.nan_to_num(DiffDiags, copy=False)
	
	EctopicArray = numpy.zeros_like(DataMut)
	
	logging.info(f"Diagonals created")
	# find ectopic interactions on diagonals of diff_array
	for k, kDiag in DiffDiags:
		NonZero = numpy.nonzero(kDiag)
		if NonZero[0].size == 0: continue
		PercTop, PercBottom = numpy.percentile(kDiag[NonZero], 96), numpy.percentile(kDiag[NonZero], 4)
		#don't use overlayers
		Diag96Perc = [item for item in kDiag if (PercBottom < item < PercTop) and (item != 0)]
		if len(Diag96Perc) < 10: continue
		DiagMean, DiagStd = numpy.mean(Diag96Perc), numpy.std(Diag96Perc)
		if DiagStd == 0: continue
		for n, Contact in enumerate(kDiag):
			if Contact != 0: EctopicArray[k + n, n] = (Contact - DiagMean) / DiagStd
	
	logging.info(f"Ectopic interactions on diagonals found")
	
	return EctopicArray

def IntersectEctopicMatrices(MatrixA, MatrixB, SD):
	Condition = lambda Matrix: numpy.logical_and(numpy.isfinite(Matrix), numpy.logical_or(Matrix < -SD, Matrix > SD))
	return numpy.sum(numpy.logical_and(Condition(MatrixA), Condition(MatrixB)))

def MakeMcool(ID, InputCool, OutputMcool, Resolution, DockerTmp):
	for Line in [f"Input COOL: {InputCool}", f"Output MCOOL: {OutputMcool}", f"Resolution: {int(Resolution / 1000)} kb"]: logging.info(Line)
	with tempfile.TemporaryDirectory() as TempDir:
		TempFile = os.path.join(TempDir, "temp.cool")
		SimpleSubprocess(Name = "CoolerZoomify", Command = f"cooler zoomify -n 8 -r {Resolution}N --balance -o \"{TempFile}\" \"{InputCool}\"")
		SimpleSubprocess(Name = "Copy2DockerTmp", Command = f"cp \"{TempFile}\" \"{os.path.join(DockerTmp, 'bm_temp.cool')}\"")
		SimpleSubprocess(Name = "HiGlassIngest", Command = f"docker exec higlass-container python higlass-server/manage.py ingest_tileset --filename \"{os.path.join('/tmp', 'bm_temp.cool')}\" --filetype cooler --datatype matrix --uid \"{ID}\" --project-name \"3DGenBench\" --name \"{ID}\" --coordSystem \"{ID}-CoordSystem\"")
		SimpleSubprocess(Name = "Copy2MCoolDir", Command = f"cp \"{TempFile}\" \"{OutputMcool}\"")

def MakeBedgraph(ID, InsDataset, OutputBedgraph, Assembly, Chrom, DockerTmp):
	for Line in [f"Output Bedgraph: {OutputBedgraph}"]: logging.info(Line)
	with tempfile.TemporaryDirectory() as TempDir:
		TempFile = os.path.join(TempDir, "temp.bedgraph")
		InsDataset.to_csv(TempFile, sep="\t", index=False, header=False)
		# InsDataset.to_csv(OutputBedgraph, sep="\t", index=False, header=False)
		#TODO check that this script is working for HiGlass
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

def PearsonCorr(SeriesA, SeriesB, method="spearman"): return SeriesA.corr(SeriesB, method=method)

def SCC(CoolA, CoolB, MaxDist, h): return hicrep.genome_scc(CoolA, CoolB, max_dist=MaxDist, h=h)

def PRCurve(YTrue, Probas):
	ReduceVector = lambda vector, maxnum: [vector[0]] + [vector[int(index * (len(vector) / maxnum))] for index in range(1, maxnum - 1)] + [vector[-1]]
	YTrueNumpy = numpy.nan_to_num(YTrue)
	ProbasNumpy = numpy.nan_to_num(Probas)
	Precision, Recall, Thresholds = precision_recall_curve(YTrueNumpy, ProbasNumpy)
	if len(Precision) > 100: 
		Precision = ReduceVector(Precision, 100)
		Recall = ReduceVector(Recall, 100)
	return {
		"AUC": auc(Recall, Precision),
		"Precision": json.dumps(list(Precision)),
		"Recall": json.dumps(list(Recall)),
		"Thresholds": json.dumps(Thresholds.tolist())
		}

def RandomEctopicIntersections(EctopicArrayExp, EctopicArrayPred, n = C_RANDOM_INTER_N, sigma = C_RANDOM_INTER_SIGMA):
	for Line in [f"N: {n}", f"Sigma: {sigma}"]: logging.info(Line)
	RandomIntersections = []
	EctopicPredRandom = numpy.copy(EctopicArrayPred)
	Finite = numpy.where(numpy.isfinite(EctopicArrayPred))
	Rand = EctopicArrayPred[Finite].flatten()
	for i in range(n):
		numpy.random.shuffle(Rand)
		EctopicPredRandom[Finite] = Rand
		RandomIntersections.append(int(IntersectEctopicMatrices(EctopicArrayExp, EctopicPredRandom, sigma)))
	ExpPredIntersection = int(IntersectEctopicMatrices(EctopicArrayExp, EctopicArrayPred, sigma))
	return {
		"Random": json.dumps(RandomIntersections),
		"Real": ExpPredIntersection
		}

def EctopicGraphArray(RawArray):
	PlottingArray = numpy.copy(RawArray)
	PlottingArray = PlottingArray.astype('float')
	PlottingArray[numpy.logical_and(PlottingArray < 2, PlottingArray > -2)] = numpy.nan
	return json.dumps(PlottingArray[150:350, 150:350].tolist())

# ------======| DRAFT VISUALIZATION |======------

def VisualizeCool(InputCool, OutputPng, Region):
	SimpleSubprocess(Name = "VisualizeCool", Command = f"cooler show --out \"{OutputPng}\" -b --dpi 150 \"{InputCool}\" {Region}")

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
	fig, ax = plt.subplots(figsize=(8,6), dpi=150)
	ax.set(title=f"Real vs Random Ectopic Intersections")
	ax.hist(Random, bins=200, histtype='step')
	ax.axvline(x=Real, color="red")
	p_value = stats.ttest_1samp(Random, Real)
	plt.title("p-value "+str(p_value))
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

def HashJSON(Object, Key): return hashlib.sha256((json.dumps(Object, ensure_ascii=False) + Key).encode('utf8')).hexdigest()

def CheckHash(Object, key):
	Object = copy.deepcopy(Object)
	ObjectHash = copy.deepcopy(Object["__hash__"])
	del Object["__hash__"]
	return HashJSON(Object, key) == ObjectHash

# ------======| PARSER |======------

def CreateParser():
	Parser = argparse.ArgumentParser(formatter_class=argparse.RawDescriptionHelpFormatter, description=f"BenchmarkPipeline by {__author__}", epilog=f"Email: regnveig@ya.ru")
	Parser.add_argument('--version', action='version', version=__version__)

	Parser.add_argument('-i', '--id', required=True, type=str, help=f"Unit ID")
	Parser.add_argument('-a', '--author', required=True, type=str, help=f"Author ID")
	Parser.add_argument('-m', '--model', required=True, type=str, help=f"Model name")
	Parser.add_argument('-s', '--sample', required=True, type=str, help=f"Sample name")
	Parser.add_argument('-r', '--resolution', required=True, type=int, help=f"Resolution")
	Parser.add_argument('-t', '--table', required=True, type=str, help=f"Main table path")
	Parser.add_argument('-W', '--wildtype', required=True, type=str, help=f"WT prediction TSV path")
	Parser.add_argument('-M', '--mutation', required=True, type=str, help=f"Mut prediction TSV path")
	Parser.add_argument('-d', '--db', required=True, type=str, help=f"SQLite DB path")
	Parser.add_argument('-c', '--cooldir', required=True, type=str, help=f"COOL root dir")
	Parser.add_argument('-l', '--log', required=True, type=str, help=f"Log file")
	
	return Parser

# ------======| FILE CREATOR |======------

def CreateDataFiles(UnitID, AuthorName, ModelName, SampleName, FileNamesInput, CoolDir, Chrom, PredictionStart, PredictionEnd, BinSize, Assembly, SampleData, SqlDB, testing=False):
	if testing:
		CoolDirID = os.path.join(CoolDir, UnitID, SampleName)
		if not os.path.exists(os.path.join(CoolDir, UnitID)):
			os.mkdir(os.path.join(CoolDir, UnitID))
	else:
		CoolDirID = os.path.join(CoolDir, UnitID)
	if not os.path.exists(CoolDirID):
		os.mkdir(CoolDirID)
	FileNamesOutput = {
		("Wt", "Exp"): os.path.join(CoolDirID, f"{UnitID}-WtExp.cool"),
		("Wt", "Pred"): os.path.join(CoolDirID, f"{UnitID}-WtPred.cool"),
		("Mut", "Exp"): os.path.join(CoolDirID, f"{UnitID}-MutExp.cool"),
		("Mut", "Pred"): os.path.join(CoolDirID, f"{UnitID}-MutPred.cool")
		}

	FileNamesBedgraphOutput = {
		("Wt", "Exp"): os.path.join(CoolDirID, f"{UnitID}-WtExp.bedgraph"),
		("Wt", "Pred"): os.path.join(CoolDirID, f"{UnitID}-WtPred.begraph"),
		("Mut", "Exp"): os.path.join(CoolDirID, f"{UnitID}-MutExp.bedgraph"),
		("Mut", "Pred"): os.path.join(CoolDirID, f"{UnitID}-MutPred.bedgraph")
	}
	# Create ts
	SubmissionDate = datetime.datetime.now(datetime.timezone.utc).isoformat()
	
	# Create data struct
	Data = {}
	
	# Create temp files
	with Timer(f"Temp files created") as _:
		if not testing:
			TempDir = tempfile.TemporaryDirectory()
			TempFiles = {Type: os.path.join(TempDir.name, f"{'-'.join(Type)}.cool") for Type in FileNamesInput.keys()}
		else:
			TempFiles = {Type: os.path.join(CoolDirID, f"{'-'.join(Type)}.cool") for Type in FileNamesInput.keys()}
		for Type, FN in FileNamesInput.items():
			if Type[1] == "Exp": Cool2Cool(FN, TempFiles[Type], Chrom)
			if Type[1] == "Pred": Tsv2Cool(FN, TempFiles[Type], TempFiles[("Wt", "Exp")], Chrom, BinSize)

	# Align
	with Timer(f"Sample type align") as _:
		if not testing:
			SampleTypeAligned = {Type: os.path.join(TempDir.name, f"{'-'.join(Type)}-SampleTypeAligned.cool") for Type in FileNamesInput.keys()}
		else:
			SampleTypeAligned = {Type: os.path.join(CoolDirID, f"{'-'.join(Type)}-SampleTypeAligned.cool") for Type
								 in FileNamesInput.keys()}
		for ST in ["Wt", "Mut"]: AlignCools(TempFiles[(ST, "Exp")], TempFiles[(ST, "Pred")], SampleTypeAligned[(ST, "Exp")], SampleTypeAligned[(ST, "Pred")], Chrom, testing=testing)
		SampleTypeAligned = {Type: cooler.Cooler(FN) for Type, FN in SampleTypeAligned.items()}
		
		# DRAFT
		for Type, Cool in SampleTypeAligned.items():
			VisualizeCool(InputCool = Cool.store, OutputPng = os.path.join(CoolDirID, f".{UnitID}-{Type[0]}{Type[1]}SampleTypeAligned.png"), Region = f"{Chrom}:{PredictionStart}-{PredictionEnd}")
		

	
	# METRICS

	# Pearson
	with Timer(f"Pearson") as _:
		Data["Metrics.Pearson.WT"] = PearsonCorr(GetMatrix(SampleTypeAligned[("Wt", "Exp")], Chrom)["balanced"], GetMatrix(SampleTypeAligned[("Wt", "Pred")], Chrom)["balanced"], method='spearman')
		Data["Metrics.Pearson.MUT"] = PearsonCorr(GetMatrix(SampleTypeAligned[("Mut", "Exp")], Chrom)["balanced"], GetMatrix(SampleTypeAligned[("Mut", "Pred")], Chrom)["balanced"], method='spearman')

	# SCC
	with Timer(f"SCC") as _:
		Data["Metrics.SCC.WT"] = SCC(SampleTypeAligned[("Wt", "Exp")], SampleTypeAligned[("Wt", "Pred")], MaxDist = C_SCC_MAXDIST, h = C_SCC_H)
		Data["Metrics.SCC.MUT"] = SCC(SampleTypeAligned[("Mut", "Exp")], SampleTypeAligned[("Mut", "Pred")], MaxDist = C_SCC_MAXDIST, h = C_SCC_H)

	# Insulation
	with Timer(f"Insulation Dataset") as _:
		InsDataset = InsulationData(SampleTypeAligned, Window=BinSize * 5, capture_start=PredictionStart, capture_end=PredictionEnd)

	with Timer(f"Insulation Score Pearson") as _:
		Data["Metrics.InsulationScorePearson.WT"] = PearsonCorr(InsDataset["sum_balanced_Wt-Exp"], InsDataset["sum_balanced_Wt-Pred"], method='spearman')
		Data["Metrics.InsulationScorePearson.MUT"] = PearsonCorr(InsDataset["sum_balanced_Mut-Exp"], InsDataset["sum_balanced_Mut-Pred"], method='spearman')

	with Timer(f"Insulation Score (Mut/Wt) Pearson") as _:
		Data["Metrics.InsulationScoreMutVsWtPearson"] = PearsonCorr(InsDataset["sum_balanced_Mut/Wt-Exp"], InsDataset["sum_balanced_Mut/Wt-Pred"], method='spearman')

	# save insulatory score bedgraphs
	with Timer(f"Save Bedgraphs") as _:
		for Key in FileNamesBedgraphOutput.keys(): MakeBedgraph(
			ID=os.path.splitext(os.path.basename(FileNamesBedgraphOutput[Key]))[0],
			InsDataset=InsDataset[["chrom", "start", "end", "sum_balanced_" + Key[0] + '-' + Key[1]]],
			OutputBedgraph=FileNamesBedgraphOutput[Key],
			Assembly=Assembly,
			Chrom=Chrom,
			DockerTmp="/home/fairwind/tmp")

	# Insulatory AUC
	#deprecated metric
	# with Timer(f"Ectopic Insulation PR Curve") as _:
	# 	EctopicInsulationPR = PRCurve(
	# 		YTrue = InsDataset["Y-True"],
	# 		Probas = InsDataset["sum_balanced_sigma_Mut/Wt-Pred"]
	# 		)
	# 	Data["Metrics.EctopicInsulation.AUC"] = EctopicInsulationPR["AUC"]
	# 	Data["Metrics.EctopicInsulation.Precision"] = EctopicInsulationPR["Precision"]
	# 	Data["Metrics.EctopicInsulation.Recall"] = EctopicInsulationPR["Recall"]
	# 	Data["Metrics.EctopicInsulation.Thresholds"] = EctopicInsulationPR["Thresholds"]

		# DRAFT
		# VisualizePR(PRData = EctopicInsulationPR, Name = "Ectopic Insulation", FN = os.path.join(CoolDirID, f".{UnitID}-EctopicInsulationPRCurve.png"))

	# Ectopic
	CoolFilesOpened = {Type: cooler.Cooler(FN) for Type, FN in TempFiles.items()}
	with Timer(f"Ectopic Array") as _:
		EctopicArrayExp = EctopicInteractionsArray(CoolFilesOpened[("Wt", "Exp")], CoolFilesOpened[("Mut", "Exp")], Chrom, PredictionStart, PredictionEnd, SampleData)
		EctopicArrayPred = EctopicInteractionsArray(CoolFilesOpened[("Wt", "Pred")], CoolFilesOpened[("Mut", "Pred")], Chrom, PredictionStart, PredictionEnd, SampleData)

	with Timer(f"Ectopic Array Graph") as _:
		Data["Metrics.EctopicArrayGraph.EXP"] = EctopicGraphArray(EctopicArrayExp)
		Data["Metrics.EctopicArrayGraph.PRED"] = EctopicGraphArray(EctopicArrayPred)

		# DRAFT
		for Key in ["Metrics.EctopicArrayGraph.EXP", "Metrics.EctopicArrayGraph.PRED"]: VisualizeEctopicArray(EctopicArray = Data[Key], FN = os.path.join(CoolDirID, f".{UnitID}-{Key}EctopicArray.png"))

	with Timer(f"Ectopic Interactions PR Curve") as _:
		EctopicInteractions = PRCurve(
			YTrue = [(i == i) and ((i > 2) or (i < -2)) for i in numpy.abs(EctopicArrayExp.flatten())],
			Probas = numpy.abs(EctopicArrayPred.flatten())
			)

		Data["Metrics.EctopicInteractions.AUC"] = EctopicInteractions["AUC"]
		Data["Metrics.EctopicInteractions.Precision"] = EctopicInteractions["Precision"]
		Data["Metrics.EctopicInteractions.Recall"] = EctopicInteractions["Recall"]
		Data["Metrics.EctopicInteractions.Thresholds"] = EctopicInteractions["Thresholds"]
		
		# DRAFT
		VisualizePR(PRData = EctopicInteractions, Name = "Ectopic Interactions", FN = os.path.join(CoolDirID, f".{UnitID}-EctopicInteractionsPRCurve.png"))

	# Random
	with Timer(f"Random Interactions") as _:
		RandomInteractions = RandomEctopicIntersections(EctopicArrayExp, EctopicArrayPred)

		Data["Metrics.RandomInteractions.Random"] = RandomInteractions["Random"]
		Data["Metrics.RandomInteractions.Real"] = RandomInteractions["Real"]

		# DRAFT
		VisualizeRandom(RandomData = RandomInteractions, FN = os.path.join(CoolDirID, f".{UnitID}-RealVsRandomEctopicInteractions.png"))

	with Timer(f"Save MCOOLs") as _:
		for Key in SampleTypeAligned.keys(): MakeMcool(ID = os.path.splitext(os.path.basename(FileNamesOutput[Key]))[0], InputCool = SampleTypeAligned[Key].store, OutputMcool = FileNamesOutput[Key], Resolution = BinSize, DockerTmp = "/home/fairwind/tmp")

	# SAVE
	with Timer(f"SQL") as _:
		try:
			sqlite_connection = sqlite3.connect(SqlDB)
			cursor = sqlite_connection.cursor()
			logging.info("DB Connected")
			AllMetricsSQL = ', '.join([f"'{key}'='{value}'" for key, value in Data.items()])
			sqlite_select_query = f"update bm_metrics set Status='0', {AllMetricsSQL} where ID='{UnitID}';"
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
	MainTable = pandas.read_csv(Namespace.table, sep='\t', dtype=str).set_index("rearrangement_ID").rename_axis(None, axis=0).transpose().to_dict()
	try:
		SampleData = MainTable[Namespace.sample]
	except KeyError:
		raise ValueError(f"Unknown Sample Name: '{Namespace.sample}'")
	
	FileNamesInput = {
		("Wt", "Exp"): os.path.join(SampleData["capture_WT_data_AlenaServer"], f"inter_{int(Namespace.resolution / 1000)}kb.cool"),
		("Wt", "Pred"): Namespace.wildtype,
		("Mut", "Exp"): os.path.join(SampleData["capture_Mut_data_AlenaServer"], f"inter_{int(Namespace.resolution / 1000)}kb.cool"),
		("Mut", "Pred"): Namespace.mutation
		}
	
	CreateDataFiles(
		UnitID = Namespace.id,
		AuthorName = Namespace.author,
		ModelName = Namespace.model,
		SampleName = Namespace.sample,
		FileNamesInput = FileNamesInput, 
		CoolDir = Namespace.cooldir,
		Chrom = SampleData["chr"],
		PredictionStart = int(SampleData["start_prediction"]),
		PredictionEnd = int(SampleData["end_prediction"]),
		BinSize = Namespace.resolution,
		Assembly = SampleData["genome_assembly"],
		SampleData = SampleData,
		SqlDB = Namespace.db)

if __name__ == "__main__": Main()
