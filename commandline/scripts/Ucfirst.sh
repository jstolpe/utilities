#!/bin/bash
for i in *; # loop over files
	do new=`echo "$i" | sed -e 's/^./\U&/'`; # create file name with first letter uppercase
	mv "$i" "$new"; # move old file to new first letter uppercase file
done; # easy