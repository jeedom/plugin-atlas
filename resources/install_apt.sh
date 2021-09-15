PROGRESS_FILE=/tmp/dependancy_atlas_in_progress
if [ ! -z $1 ]; then
	PROGRESS_FILE=$1
fi
touch ${PROGRESS_FILE}
echo 0 > ${PROGRESS_FILE}
echo "Launch install of Atlas dependancies : "
BASEDIR=$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )
sudo apt-get clean
echo 30 > ${PROGRESS_FILE}
sudo apt-get update
echo 40 > ${PROGRESS_FILE}
sudo apt-get install -y python3 python3-pip python3-pyudev python3-requests python3-dev
echo 60 > ${PROGRESS_FILE}
sudo pip3 install setuptools
echo 70 > ${PROGRESS_FILE}
sudo pip3 install --upgrade wheel
echo 80 > ${PROGRESS_FILE}
sudo pip3 install nmcli
echo 90 > ${PROGRESS_FILE}
rm ${PROGRESS_FILE}
echo "Everything is successfully installed!"
