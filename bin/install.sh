#!/bin/bash
##
# Installation script for mbc-registration-mobile
##

# Assume messagebroker-config repo is one directory up
cd ../messagebroker-config

# Gather path from root
MBCONFIG=`pwd`

# Back to mbc-registration-mobile
cd ../mbc-user-import

# Create SymLink for mbc-registration-mobile application to make reference to for all Message Broker configuration settings
ln -s $MBCONFIG .
