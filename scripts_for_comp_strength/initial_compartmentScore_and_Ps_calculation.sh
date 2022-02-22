nbins=1000
compartmentLength=25

# Case with no compartmentalization
outfile=input_matrix_random.tab
awk -v min=50 -v max=100 -v nb=${nbins} 'BEGIN{srand(); for(i=0;i<nb;i++){for(j=i;j<nb;j++){if(i==j){d=1}else{d=j-i};random=int(min+rand()*(max-min+1)); print i,j,random/d}}}' > ${outfile}
# Plot matrix
cp ${outfile} _tmp.tab
sed "s/XXXsizeXXX/${nbins}/g" scripts/plot_contact_matrix.gp | gnuplot ; bash scripts/ps2pdf.sh
mv contact_map.png ${outfile%tab}png
rm contact_map.pdf

# Case with compartmentalization
outfile=input_matrix_domains.tab
awk -v cl=${compartmentLength} -v imin=90 -v imax=100 -v emin=50 -v emax=100 -v nb=${nbins} 'BEGIN{srand(); for(i=0;i<nb;i++){for(j=i;j<nb;j++){if((int(i/cl)%2) == (int(j/cl)%2)){tag="same"; min=imin; max=imax}else{tag="else"; min=emin; max=emax}; if(i==j){d=1}else{d=j-i};random=int(min+rand()*(max-min+1)); print i,j,random/d,tag}}}' > ${outfile}
# Plot matrix
cp ${outfile} _tmp.tab
sed "s/XXXsizeXXX/${nbins}/g" scripts/plot_contact_matrix.gp | gnuplot ; bash scripts/ps2pdf.sh
mv contact_map.png ${outfile%tab}png
rm contact_map.pdf

# Write file with compartment partition. compartment_partition.txt has 2 columns: bin | compartment
# head -5 compartment_partition.txt
#0 A 
#1 A 
#2 A 
#3 A 
#4 A 
awk -v cl=${compartmentLength} -v nb=${nbins} 'BEGIN{for(i=0;i<nb;i++){if((int(i/cl)%2)==0){printf("%d A ",i)}else{printf("%d B ",i)}; if(i==20 || i==80 || i==800){printf("MASKED\n")}else{printf("\n")}}}' > compartment_partition.txt

for system in random domains ;
do
    CSoutfile=compartment_strength_per_bin_${system}.txt
    infile=input_matrix_${system}.tab

    # Expected number of contacts at fixed genomic separation
    indecay=average_number_of_contacts_vs_gendist_matrix_${system}.txt
    # NOTE: cnt[i] should take into account values equal to zero, but not MASKED bins (i.e. bins with too few contacts)
    awk -v nb=${nbins} '{if(NF==2){masked[$1]=1}else{if(masked[$1]==1 || masked[$2]==1){next}; d=sqrt(($1-$2)*($1-$2)); h[d]+=$3; cnt[d]++}}END{for(i in h) print i,h[i]/cnt[i]}' <( grep MASKED compartment_partition.txt | awk '{print $1,$3}' ) ${infile} | sort -k 1n > ${indecay}
    decay=average_number_of_contacts_with_stddev_vs_gendist_matrix_${system}.txt
    awk -v nb=${nbins} '{if(NF==2){masked[$1]=1}else{if(masked[$1]==1 || masked[$2]==1){next}; d=sqrt(($1-$2)*($1-$2)); h[d]+=$3; h2[d]+=$3*$3; cnt[d]++}}END{for(i in h){avg=h[i]/cnt[i]; avg2=h2[i]/cnt[i]; stddev=sqrt(avg2-avg*avg); print i,avg,stddev}}' <( grep MASKED compartment_partition.txt | awk '{print $1,$3}' ) ${infile} | sort -k 1n > ${decay}
    cp ${decay} _tmp
    gnuplot scripts/plot_Ps.gp 
    bash scripts/ps2pdf.sh
    mv _tmp.png ${decay%txt}png
    
    # Compute ObsOverExp matrix. NOTE: for stability of the code a pseudo-count is added to the Expected
    outfile=${infile%.tab}_ObsOverExp.tab
    awk '{if(NF==2){e[$1]=$2+1}else{d=sqrt(($1-$2)*($1-$2)); print $1,$2,$3/e[d]}}' ${indecay} ${infile} > ${outfile}
    # Add the chromatin state per bin to the ObsOverExp matrix. NOTE: MASKED bins will be excluded!
    awk '{if(NF==2){cs[$1]=$2}else{print $1,cs[$1],$2,cs[$2],$3}}' compartment_partition.txt ${outfile} | awk '{if(NF==5) print $0}' > _tmp ; mv _tmp ${outfile}

    # Compute CS per bin. We have to define a numerator and a denominator per bin, and get their average. The ratio numerator/denominator is the compartment strength per bin
    awk '{if($2==$4){num[$1]+=$5; num[$3]+=$5; cnum[$1]++; cnum[$3]++}; if($2!=$4){den[$1]+=$5; den[$3]+=$5; cden[$1]++; cden[$3]++}; cs[$1]=$2; cs[$3]=$4}END{for(i in num){print i,cs[i],(num[i]/cnum[i])/(den[i]/cden[i])}}' ${outfile} | sort -k 1n > ${CSoutfile}

    awk '{print $2,$3}' ${CSoutfile} > _tmp.txt
    Rscript scripts/compartmentScore_calculation.R
    mv _tmp.pdf ${CSoutfile%txt}pdf
       
done

rm _*
