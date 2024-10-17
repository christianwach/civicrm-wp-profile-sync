#! /bin/bash

# ----------------------------------------------------------------------------------------
# Title:       Deploy to WordPress Plugin Directory.
# Description: Deploys the latest version of this plugin to the WordPress Plugin Directory.
# Version:     1.0.0
# ----------------------------------------------------------------------------------------
# A modification of Dean Clatworthy's deploy script as found here:
# @see https://github.com/deanc/wordpress-plugin-git-svn
# This script lives in the plugin's git repo & doesn't require an existing SVN repo.
# ----------------------------------------------------------------------------------------

# Main config.
# This should be the slug of your plugin.
PLUGINSLUG="civicrm-wp-profile-sync"
# This should be the name of your main PHP file in the WordPress plugin.
MAINFILE="civicrm-wp-profile-sync.php"

# git config
CURRENTDIR=`pwd`
# This file should be in the base of your git repository.
GITPATH="${CURRENTDIR}/"

# svn config
# Path to a temp SVN repo. No trailing slash required and don't add trunk.
SVNPATH="/tmp/${PLUGINSLUG}"
# Remote SVN repo on wordpress.org, with no trailing slash.
SVNURL="https://plugins.svn.wordpress.org/${PLUGINSLUG}/"
# Your svn username.
SVNUSER="needle"

# ----------------------------------------------------------------------------------------

# Let's begin.
echo ".........................................."
echo "Preparing to deploy WordPress plugin"
echo ".........................................."
echo

# Check version in readme.txt is the same as plugin file after translating both to
# Unix line breaks to work around grep's failure to identify Mac line breaks.
NEWVERSION1=`grep "^Stable tag:" ${GITPATH}/readme.txt | awk -F' ' '{print $NF}'`
echo "readme.txt version: ${NEWVERSION1}"
NEWVERSION2=`grep "^ \* Version:" ${GITPATH}/${MAINFILE} | awk -F' ' '{print $NF}'`
echo "${MAINFILE} version: ${NEWVERSION2}"
if [ "${NEWVERSION1}" != "${NEWVERSION2}" ]; then
	echo "Version in readme.txt & ${MAINFILE} don't match. Exiting.";
	exit 1;
fi

echo "Versions match in readme.txt and ${MAINFILE}. Let's proceed..."

if git show-ref --tags --quiet --verify -- "refs/tags/${NEWVERSION1}"
	then
		echo "Version ${NEWVERSION1} already exists as git tag. Exiting.";
		exit 1;
	else
		echo "git version does not exist. Let's proceed..."
fi

# ----------------------------------------------------------------------------------------
# Okay, let's do git housekeeping.
# ----------------------------------------------------------------------------------------

cd $GITPATH
echo -e "Enter a commit message for this new version: \c"
read COMMITMSG
git commit -am "${COMMITMSG}"

echo "Tagging new version in git"
git tag -a "${NEWVERSION1}" -m "Tagging version ${NEWVERSION1}"

echo "Pushing latest commit to origin, with tags"
git push origin master
git push origin master --tags

# ----------------------------------------------------------------------------------------
# Now do git-to-svn procedure.
# ----------------------------------------------------------------------------------------

echo
echo "Creating local copy of SVN repo..."
svn co $SVNURL $SVNPATH

echo "Exporting the HEAD of master from git to the trunk of SVN"
git checkout-index -a -f --prefix=$SVNPATH/trunk/

echo "Ignoring GitHub specific files and deployment script"
svn propset svn:ignore "deploy.sh
README.md
phpcs.xml
docs
screenshots
.editorconfig
.git
.gitignore
.phpcs
.DS_Store" "${SVNPATH}/trunk/"

echo "Changing directory to SVN and committing to trunk"
cd $SVNPATH/trunk/

# Remove "GitHub Plugin URI" line.
# mv "${MAINFILE}" tmp.php
# sed '/GitHub Plugin URI:/d' tmp.php > "${MAINFILE}"
# rm tmp.php

# Add all new files that are not set to be ignored.
svn status | grep -v "^.[ \t]*\..*" | grep "^?" | awk '{print $2}' | xargs svn add

# TODO - remove files that were deleted from Git repo.

svn commit --username=$SVNUSER -m "${COMMITMSG}"

echo "Creating new SVN tag & committing it"
cd $SVNPATH
svn copy trunk/ tags/$NEWVERSION1/
cd $SVNPATH/tags/$NEWVERSION1
svn commit --username=$SVNUSER -m "Tagging version ${NEWVERSION1}"

# Clean up.
echo "Removing temporary directory ${SVNPATH}"
rm -fr $SVNPATH/
echo "*** All done! ***"
