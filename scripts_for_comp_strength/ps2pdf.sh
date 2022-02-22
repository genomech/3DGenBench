#!/usr/bin/env bash

for file in $(ls *.ps); 
do 
    echo $file

    ps2pdf $file
    
    pdfcrop --margin 0 ${file%.ps}.pdf _tmp_${file%.ps}.pdf #&> /dev/null
    mv _tmp_${file%.ps}.pdf ${file%.ps}.pdf
    convert -background white -alpha remove -density 300 ${file%ps}pdf ${file%ps}png
    
    rm $file
done
