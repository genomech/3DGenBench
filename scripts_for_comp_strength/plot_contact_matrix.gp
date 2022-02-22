#!/usr/bin/gnuplot -persist
#
#    
#    	G N U P L O T
#    	Version 5.0 patchlevel 0    last modified 2015-01-01 
#    
#    	Copyright (C) 1986-1993, 1998, 2004, 2007-2015
#    	Thomas Williams, Colin Kelley and many others
#    
#    	gnuplot home:     http://www.gnuplot.info
#    	faq, bugs, etc:   type "help FAQ"
#    	immediate help:   type "help"  (plot window: hit 'h')
# set terminal x11 
# set output
unset clip points
set clip one
unset clip two
set bar 1.000000 front
set border 31 front lt black linewidth 1.000 dashtype solid
set zdata 
set ydata 
set xdata 
set y2data 
set x2data 
set boxwidth
set style fill  empty border
set style rectangle back fc  bgnd fillstyle   solid 1.00 border lt -1
set style circle radius graph 0.02, first 0.00000, 0.00000 
set style ellipse size graph 0.05, 0.03, first 0.00000 angle 0 units xy
set dummy x, y
set format x "% h" 
set format y "% h" 
set format x2 "% h" 
set format y2 "% h" 
set format z "% h" 
set format cb "% h" 
set format r "% h" 
set timefmt "%d/%m/%y,%H:%M"
set angles radians
unset grid
set raxis
set style parallel front  lt black linewidth 2.000 dashtype solid
set key title ""
set key inside right top vertical Right noreverse enhanced autotitle nobox
set key noinvert samplen 4 spacing 1 width 0 height 0 
set key maxcolumns 0 maxrows 0
set key noopaque
unset label
unset arrow
set style increment default
unset style line
unset style arrow
set style histogram clustered gap 2 title textcolor lt -1
unset object
set style textbox transparent margins  1.0,  1.0 border
#set logscale cb 
set offsets 0, 0, 0, 0
set pointsize 0.2
set pointintervalbox 1
set encoding default
unset polar
unset parametric
unset decimalsign
set view 60, 30, 1, 1
set samples 100, 100
set isosamples 10, 10
set surface 
unset contour
set cntrlabel  format '%8.3g' font '' start 5 interval 20
set mapping cartesian
set datafile separator whitespace
unset hidden3d
set cntrparam order 4
set cntrparam linear
set cntrparam levels auto 5
set cntrparam points 5
set size ratio 1
set origin 0,0
set style data points
set style function lines
unset xzeroaxis
unset yzeroaxis
unset zzeroaxis
unset x2zeroaxis
unset y2zeroaxis
set ticslevel 0.5
set tics scale  1, 0.5, 1, 1, 1
set mxtics default
set mytics default
set mztics default
set mx2tics default
set my2tics default
set mcbtics default
set mrtics default
set x2tics border out scale 1,0.5 nomirror norotate  autojustify
set x2tics autofreq  norangelimit
unset x2tics
set ytics border out scale 1,0.5 nomirror norotate  autojustify
set ytics autofreq  norangelimit

set xtics border out scale 1,0.5 nomirror norotate  autojustify
set xtics autofreq  norangelimit

set ztics border in scale 1,0.5 nomirror norotate  autojustify
set ztics autofreq  norangelimit

#unset y2tics
set cbtics border in scale 1,0.5 mirror norotate  autojustify
set cbtics autofreq  norangelimit
set rtics axis in scale 1,0.5 nomirror norotate  autojustify
set rtics autofreq  norangelimit
unset paxis 1 tics
unset paxis 2 tics
unset paxis 3 tics
unset paxis 4 tics
unset paxis 5 tics
unset paxis 6 tics
unset paxis 7 tics
set title "" 
set title  font "" norotate
set timestamp bottom 
set timestamp "" 
set timestamp  font "" norotate
set rrange [ * : * ] noreverse nowriteback
set trange [ * : * ] noreverse nowriteback
set urange [ * : * ] noreverse nowriteback
set vrange [ * : * ] noreverse nowriteback
#set xlabel "NBC"
set xlabel  font "Bold Arial, 28" textcolor lt -1 norotate
#set x2label "GCBC"
set x2label  font "Bold Arial, 28" textcolor lt -1 norotate


set ylabel  font "Arial, 28" textcolor lt -1 rotate by -270

set y2label  font "Arial, 28" textcolor lt -1 rotate by -270

set zlabel "" 
set zlabel  font "" textcolor lt -1 norotate
set zrange [ * : * ] noreverse nowriteback
set cblabel "" 
set cblabel  font "" textcolor lt -1 rotate by -270

set paxis 1 range [ * : * ] noreverse nowriteback
set paxis 2 range [ * : * ] noreverse nowriteback
set paxis 3 range [ * : * ] noreverse nowriteback
set paxis 4 range [ * : * ] noreverse nowriteback
set paxis 5 range [ * : * ] noreverse nowriteback
set paxis 6 range [ * : * ] noreverse nowriteback
set paxis 7 range [ * : * ] noreverse nowriteback
set zero 1e-08
set lmargin  -1
set bmargin  -1
set rmargin  -1
set tmargin  -1
set locale "C"
set pm3d explicit at s
set pm3d scansautomatic
set pm3d interpolate 1,1 flush begin noftriangles noborder corners2color mean
set palette positive nops_allcF maxcolors 0 gamma 1.5 color model RGB 
set colorbox default
set colorbox vertical origin screen 0.9, 0.2, 0 size screen 0.05, 0.6, 0 front bdefault
set style boxplot candles range  1.50 outliers pt 7 separation 1 labels auto unsorted
set loadpath 
set fontpath 
set psdir
set fit brief errorvariables nocovariancevariables errorscaling prescale nowrap v5

set palette defined (0 "white", 1. "red")
set cbrange [ 0 : 5 ] noreverse nowriteback

set term post color enhanced


set xrange  [ -0.50000 : XXXsizeXXX-0.5 ] noreverse nowriteback
set xrange  [ -0.50000 : XXXsizeXXX-0.5 ] noreverse nowriteback
set yrange  [ -0.50000 : XXXsizeXXX-0.5 ] noreverse nowriteback
set y2range [ -0.50000 : XXXsizeXXX-0.5 ] noreverse nowriteback

set output "contact_map.ps"

plot "< awk 'BEGIN{for(i=0;i<XXXsizeXXX;i++)for(j=0;j<XXXsizeXXX;j++) m[i,j]=0.0}{if($1==$2 || sqrt(($1-$2)*($1-$2))<2){v=0}else{v=$3}; m[$1,$2]=v; m[$2,$1]=v}END{for(i=0;i<XXXsizeXXX;i++)for(j=0;j<XXXsizeXXX;j++) print i,j,m[i,j]}' _tmp.tab" u 1:2:3 notitle w image pixel

#    EOF
