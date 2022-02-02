data <- read.table('_tmp.txt')
library(scales) # to access break formatting functions
library(ggplot2)
library(dplyr)
args = commandArgs(trailingOnly=TRUE)

h = 0.8

colors=c("red","blue")

pdf('_tmp.pdf')

summ <- data %>%
  group_by(V1) %>%
  summarize(n = n(), V2 = h)
  
ggplot(data, aes(x=V1, y=V2, color = NA, fill=V1), alpha=0.7) + geom_violin(trim=F) + theme(axis.title = element_text(face="bold",size=24), axis.text = element_text(face="bold",size=11)) + labs(x="Compartments",y="Compartment strength",fill="Compartments") + geom_boxplot(width=0.1, fill="white", color="black") + scale_color_manual(values=colors) + scale_fill_manual(values=colors) + geom_text(aes(label = n, color=V1), color=colors, data = summ)

dev.off()


