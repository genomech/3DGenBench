 <?php

require_once('shared.php');

echo GetHeader('Benchmarking Predictive Models of 3D Genome Organization.');

?>

<h2>Aim of the Project</h2>

<p>Compare the performance of predictive models of chromatin organization and understand which chromatin features define 3D-genome architecture in normal and mutated genomes.</p>

<h2>Project overview</h2>

<p>We <a href="datasets.php" target="blank">collected</a> the dataset containing capture Hi-C and epigenetics data for wild-type and mutated mouse and human cell types.</p>

<img src="images/infographics.png" width="80%">

<p>The current dataset contains samples from several dozen mouse lines harboring genomic mutations with the known effect on chromatin organization, including data from the groups of Stefan Mundlas, Denis Duboule, Douglas Higgs, Laura Lettice, John Rinn and Narimann Battulin groups.
If you have generated 3C-data describing changes of chromatin architecture caused by genetic mutations in human and/or mouse cells, which is not currently in the dataset, please <a href="minja@bionet.nsc.ru" target="blank">let us know</a> or <a href="https://docs.google.com/forms/d/e/1FAIpQLSf9JaEds4OTjdR_awMPVrQK5SwmIluRzuAvZTOZ2zuFlv0AXg/viewform" target="blank">submit</a> it directly to the dataset.</p>

<p>The data from all sources is now being re-processed to standardize formats and allow uniform comparisons. The uniformly processed data will be next used by participants to generate models/predict effects of genetic mutations on chromatin architecture. Results submitted by participants will be scored according to the predefined accuracy metrics.</p>

<p>Now we are at the last stage of data preprocessing, and we are open to discuss the final pipeline suitable for each invited research group as well as benchmarking criteria. For discussions please <a href="https://join.slack.com/t/inc-cost/shared_invite/zt-xg9kj029-XELkAXaOSZisH_Zk9Iuigw" target="blank">join</a> INC-COST slack (channel #benchmarking).</p>

<h2>How to contribute</h2>

<ul>
	<li>Join: <a href="https://join.slack.com/t/inc-cost/shared_invite/zt-10brfgiqv-csCRDbvTc0B4EC5iy1u2xQ" target="blank">INC-COST Slack</a> (channel #benchmarking): discussing data formats and benchmark metrics</li>
	<li>Contact us if you have experimental Hi-C (or capture Hi-C) data and/or work with the code at <a href="https://github.com/regnveig/3DGenBench" target="blank">GitHub repository</a>.</li>
	<li>Test: <a href="submission_form.php" target="blank">upload</a> your predictions to our web server to benchmark your method against experimental data.</li>
	<li><a href="datasets.php" target="blank">View</a> current experimental datasets.</li>
</ul>

<h2>Contact Us</h2>

<p>Veniamin Fishman [<a href="mailto:minja@bionet.nsc.ru" target="_blank">minja@bionet.nsc.ru</a>]</p>

<p>Genomic Mechanisms of Development, <a href="http://www.bionet.nsc.ru/en" target="_blank">Institute of Cytology and Genetics SB RAS</a></p>';

<?php echo GetFooter(); ?> 
 
