nbins=$1;
system=$2;
WORK_DIR=$3;
CSoutfile=$WORK_DIR/compartment_strength_per_bin_${system}.txt
infile=$WORK_DIR/input_matrix_${system}.tab
compartment_partition_file=$WORK_DIR/compartment_partition.txt

# Expected number of contacts at fixed genomic separation
indecay=$WORK_DIR/average_number_of_contacts_vs_gendist_matrix_${system}.txt
# NOTE: cnt[i] should take into account values equal to zero, but not MASKED bins (i.e. bins with too few contacts)
awk -v nb=${nbins} '{if(NF==2){masked[$1]=1}else{if(masked[$1]==1 || masked[$2]==1){next}; d=sqrt(($1-$2)*($1-$2)); h[d]+=$3; cnt[d]++}}END{for(i in h) print i,h[i]/cnt[i]}' <( grep MASKED ${compartment_partition_file} | awk '{print $1,$3}' ) ${infile} | sort -k 1n > ${indecay}
decay=$WORK_DIR/average_number_of_contacts_with_stddev_vs_gendist_matrix_${system}.txt
awk -v nb=${nbins} '{if(NF==2){masked[$1]=1}else{if(masked[$1]==1 || masked[$2]==1){next}; d=sqrt(($1-$2)*($1-$2)); h[d]+=$3; h2[d]+=$3*$3; cnt[d]++}}END{for(i in h){avg=h[i]/cnt[i]; avg2=h2[i]/cnt[i]; stddev=sqrt(avg2-avg*avg); print i,avg,stddev}}' <( grep MASKED ${compartment_partition_file} | awk '{print $1,$3}' ) ${infile} | sort -k 1n > ${decay}
#cp ${decay} _tmp
#gnuplot plot_Ps.gp 
#bash ps2pdf.sh
#mv _tmp.png ${decay%txt}png

# Compute ObsOverExp matrix. NOTE: for stability of the code a pseudo-count is added to the Expected
outfile=${infile%.tab}_ObsOverExp.tab
awk '{if(NF==2){e[$1]=$2+1}else{d=sqrt(($1-$2)*($1-$2)); print $1,$2,$3/e[d]}}' ${indecay} ${infile} > ${outfile}
# Add the chromatin state per bin to the ObsOverExp matrix. NOTE: MASKED bins will be excluded!
awk '{if(NF==2){cs[$1]=$2}else{print $1,cs[$1],$2,cs[$2],$3}}' ${compartment_partition_file} ${outfile} | awk '{if(NF==5) print $0}' > $WORK_DIR/_tmp ; mv $WORK_DIR/_tmp ${outfile}

# Compute CS per bin. We have to define a numerator and a denominator per bin, and get their average. The ratio numerator/denominator is the compartment strength per bin
awk '{if($2==$4){num[$1]+=$5; num[$3]+=$5; cnum[$1]++; cnum[$3]++}; if($2!=$4){den[$1]+=$5; den[$3]+=$5; cden[$1]++; cden[$3]++}; cs[$1]=$2; cs[$3]=$4}END{for(i in num){print i,cs[i],(num[i]/cnum[i])/(den[i]/cden[i])}}' ${outfile} | sort -k 1n > ${CSoutfile}

#awk '{print $2,$3}' ${CSoutfile} > _tmp.txt
#Rscript compartmentScore_calculation.R
#mv _tmp.pdf ${CSoutfile%txt}pdf


#rm _*