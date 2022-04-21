{
	if($2==$4)
	{
		num[$1]+=$5; 
		num[$3]+=$5; 
		cnum[$1]++; 
		cnum[$3]++
	}; 
	if($2!=$4)
	{
		den[$1]+=$5; 
		den[$3]+=$5; 
		cden[$1]++; 
		cden[$3]++; 
		cs[$1]=$2; 
		cs[$3]=$4;
	};
}
	END{
		for(i in num){
			if(cnum[i]==0) {
				AABB=0;
			}
			else {
				AABB=num[i]/cnum[i];
			};

			if(cden[i]==0) {
				ABAB=0;
			}
			else {
				ABAB=den[i]/cden[i];
			};
			if(ABAB+AABB==0) {
				print i,cs[i],-1;
				continue;
			};
			print i,cs[i],AABB/(ABAB+AABB);
	}
}