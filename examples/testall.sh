#!/bin/bash
for s in ./test_*.php; do
  if test -f $s; then 
    php $s
  fi
done

