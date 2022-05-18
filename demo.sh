#!/bin/bash

bin/php-reflection-cli find:classes -e src/ \
  | grep Command \
  | xargs -i -n1 bin/php-reflection-cli find:methods --short {}
