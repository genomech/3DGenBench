<p style="color: black;">
3DGenBench is a web server for scoring performance of 3D genomic models.
3DGenBench provides two challenges.
The first challenge aims at quantifying how accurate a model predicts experimental data.
The second benchmark aims to estimate how well a model can predict changes in chromosome folding caused by structural genomic mutations.
</p>

<h1>Overview</h1>

<p style="color: black;">
There are four steps required to obtain 3DGenBench scores:
</p>

<p style="margin-left: 20px; color: black;">
<strong>Step 1</strong>. <a href="#Explore_hic_dataset">Explore reference Hi-C dataset</a><br>
<strong>Step 2</strong>. <a href="#predict">Generate computational predictions of Hi-C contacts for one or multiple samples</a>
(<a href="https://genedev.bionet.nsc.ru/hic_out/by_Project/INC_COST_3DBenchmark/predicted_examples/">see example data</a>)<br>
<strong>Step 3</strong>. <a href="#uploading">Upload your predictions to 3DGenBench server</a><br>
<strong>Step 4</strong>. <a href="#compute_metrics">Provide samples metadata and compute metrics</a><br>
<strong>Step 5</strong>. <a href="#explore_metrics">Explore metrics</a>
</p>

<h2><a name="Explore_hic_dataset"><strong>Step 1.</strong> Exploring the Hi-C dataset</a></h2>

<p style="color: black;">
There are two main datasets for prediction. Rearrangements dataset (paired) contains capture Hi-C and complementary epigenetic data, such as CTCF ChIP-seq, for wild-type and mutated samples (<i>Mus musculus</i>, <i>Homo sapiens</i> cell lines). Genome regions dataset (single) contains loci for prediction larger than 10 MB without mutations. These datasets can be found under <button disabled><b>DATASETS</b></button> button at the main page.
</p>

<p style="color: black;">
Samples metadata include the following information:
</p>

<p style="color: black;">
<strong>chr</strong>, <strong>start prediction</strong> and <strong>end prediction</strong> columns describe the genomic region for which Hi-C interactions expected to be predicted.
<strong>Rearr #n Start</strong>, <strong>Rearr #n end</strong> columns describe the rearrangement coordinates.
Each sample has several columns for rearrangement coordinates if several simultaneous mutations have been introduced.
The type of rearrangement can be found in the <strong>Rearrangement Type</strong> column.
Also pay attention to the assembly, cell type and available resolutions (5kb, 10kb, 20kb, 50kb).
</p>

<p style="color: black;">
Hi-C maps for wild-type and mutated conditions are available in the most commonly used formats: <a href="https://github.com/aidenlab/juicer/wiki/Data/#hic-files">hic</a>, <a href="https://cooler.readthedocs.io/en/latest/schema.html">cool</a> (for 5kb, 10kb, 20kb, 50kb resolutions), and pairs.
Also, for most of datasets there are supplementary tracks describing CTCF binding.
All the data can be downloaded via hyperlinks in the table.
If you want to download all available Hi-C data for the particular sample, please follow links from the columns <strong>WT Archived Data</strong> or <strong>MUT Archived Data</strong>.
</p>

<p style="color: black;">
If you need a particular file, you can download it manually using hyperlinks from <strong>WT FTP Folder</strong> or <strong>MUT FTP Folder</strong> columns, which lead to the local FTP storage.
</p>

<figure><img src="/wp-content/uploads/2021/12/example_files.png" alt="" class="wp-image-246" width="407" height="424"></figure>

<p style="color: black;">
The detailed description of files you can find <a href="/index.php/tutorial/" target="_blank" rel="noopener">here</a>.
</p>

<p style="color: black;">
If you want to download the whole Hi-C dataset, use command:
</p>

<pre style="color: black;">wget -r -np https://genedev.bionet.nsc.ru/hic_out/by_Project/INC_COST_3DBenchmark/hic_dataset_zipped/</pre>

<p style="color: black;">
Note that complete dataset size is about 300 GB.
</p>

<p style="color: black;">
Also, you can download CTCF data in narrowPeak data format using links from the column <strong>CTCF Data</strong>.
These files have 2 additional columns with information about CTCF binding site orientation received by <a href="https://github.com/vanheeringen-lab/gimmemotifs">GimmeMotifs</a> program.
</p>

<p style="color: black;">
If you want to download the whole CTCF data dataset:
</p>

<pre style="color: black;">wget -r -np https://genedev.bionet.nsc.ru/hic_out/by_Project/INC_COST_3DBenchmark/CTCF_data/</pre>

<h2><a name="predict"><strong>Step 2.</strong> Predicting Hi-C contacts</a></h2>

<p style="color: black;">
Use your computational model to predict Hi-C contacts for one of the reference samples.
The predicted list of contacts should be prepared in a tab-delimeted text file containing the following columns:
</p>

<pre style="color: black;">chr	contact_start	contact_end	contact_count</pre>

<p style="color: black;">
Please do not add header to the file.
An example file could be downloaded <a href="https://genedev.bionet.nsc.ru/hic_out/by_Project/INC_COST_3DBenchmark/predicted_examples/" target="_blank" rel="noopener">here</a>.
</p>

<h2><a name="uploading"><strong>Step 3.</strong> Upload your predicted data to 3DGenBench</a></h2>

<p style="color: black;">
Please upload your data via FTP using any FTP client, such as <a href="https://filezilla-project.org/">FileZilla</a> or <a href="https://winscp.net/eng/download.php">WinSCP</a>.
</p>

<p style="margin-left: 20px; color: black;">
<b>Host name:</b> ftp.alena-spn.cytogen.ru<br>
<b>Port number:</b> 8232<br>
<b>Username:</b> ftpguest<br>
<b>Password:</b> 3DGenBench<br>
</p>

<p style="color: black;">
Please ensure that passive mode is enabled by the FTP client.
</p>

<p style="color: black;">
The uploaded files will appear in drop-down list at the next (submission) step.
</p>

<h2><a name="compute_metrics"><strong>Step 4.</strong> Provide samples metadata and compute metrics</a></h2>

<p style="color: black;">
Once the data is uploaded, click the <button disabled>COMPUTE METRICS</button> button at home menu, chose the type of prediction (single or paired) and fill all the fields related to the predicted sample.
You can use <button disabled>Add test unit</button> button to load example of predicted contacts file.
Alternatively, example samples can be loaded as shown in the figure below.
</p>

<figure><img src="/wp-content/uploads/2021/12/example_input-1024x349.png" alt="" class="wp-image-326"></figure>

<p style="color: black;">
The page allows you to submit predictions for several samples using the <button disabled>Add unit</button> button.
</p>

<h2><a name="explore_metrics"><strong>Step 5.</strong> Explore metrics</a></h2>

<p style="color: black;">
The status of the submission is available by <button disabled>EXPLORE RESULTS</button> button at home menu or <button disabled>SUBMISSIONS LIST</button> button at top menu.
<strong><font color="orange">Yellow</font></strong> status of submission indicates your job is running at the server, <strong><font color="green">green</font></strong> status shows that the job was successfully completed, and <strong><font color="red">red</font></strong> indicates that there was an error with data processing.
</p>

<p style="color: black;">
When the data processing is over, you can click the ID (e.g., <a href="">bmXXXXXXXX</a>) link which redirects you to the page with job results.
This page contains metrics describing the prediction accuracy of your model (see <a href="#metrics_meaning">the section below</a>).
You can find the example of computed metrics choosing any ID with the green status.
</p>

<h3><a name="metrics_meaning">What do the output metrics mean?</a></h3>

<p style="color: black;">
These metrics reflect how well the model predicts experimental Hi-C data:
</p>

<ul style="color: black;">
<li>Pearson’s correlation between experimental and predicted Hi-C matrices</li>
<li>SCC (stratum adjusted correlation coefficient) from <a href="https://doi.org/10.1101/gr.220640.117">Yang et al. (2017)</a>, implemented by <a href="https://github.com/cmdoret/hicreppy" target="blank" rel="noopener">hicreppy</a>, between experimental and predicted Hi-C matrices</li>
<li>Pearson’s correlation of TAD-separation score at each bin (computed using <a href="https://github.com/deeptools/HiCExplorer">HiCExplorer</a> hicFindTADs)</li>
</ul>

<p style="color: black;">
These metrics reflect how well the model captures differences in genome architecture caused by mutations:
</p>

<ul style="color: black;">
<li>Ectopic interactions computed as in <a href="https://doi.org/10.1038/s41588-018-0098-8">Simona Bianco et al. (2018)</a>.
Briefly, we subtract WT Hi-C map from Mutated Hi-C map, distance-normalize the results, and compute those values which are 3 standard deviations from the mean of the distribution of the observed differences.
These outliers are designed as ectopic interactions.</li>
</ul>

<p style="color: black;">
To provide quantitative measurement of ectopic interactions overlap, we use visualization of precision-recall curves, output area under the curve metrics and show the overlap of the predicted and experimentally measured ectopic interactions as compared to randomized controls:</p>

<ul style="color: black;">
<li>Changes in TAD-separation score.
For calculating ectopic insulation score, we divide the TAD-separation score (computed using <a href="https://github.com/deeptools/HiCExplorer">HiCExplorer</a> hicFindTADs) at each bin for WT and Mutation conditions and divide one track by another element-wise.
This gives us fold changes of the TAD separation score for each locus (bin).
Then, we design the values falling above 3 standard deviations of the distributions of fold changes as ectopic insulation.</li>
<li>To provide quantitative measurement of ectopic insulatory score changes, we use measures of the overlap between ectopic insulation points identified using experimental and predicted maps (recall, precision, area under precision-recall curve).</li>
</ul>
Two additional metrics are used for comparison of predicted and experimental hi-c contacts in te case of Genome regions dataset (single):
<li>Pearson's correlation between experimental and predicted decay of contact frequency with genomic distance P(s).</li>
<li>Pearson's correlation between experimental and predicted compartment strength computetd as in <a href="https://doi.org/10.1038/s41586-019-1275-3">Martin Falk et al. (2019)</a>.
