#!/bin/bash
set -e

# Change to the script's working directory
SCRIPT_DIR=$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)
cd "$SCRIPT_DIR"

# Despite the code above, quickly confirm our assumption
CURRENT_DIR=$(pwd)
if [[ ! "$CURRENT_DIR" == *"/ext/quiz" ]]; then
  echo "ERROR"
  echo "$CURRENT_DIR"
  echo "You must run update.sh from the same folder"
  echo "It was detected that this is not the case"
  echo "ABORTING"
  exit 1
fi

cd ..

echo "Downloading ZIP file..."
# No Git Bash support: wget https://github.com/ljacqu/NightbotQuiz/archive/refs/heads/master.zip
curl -kLSs https://github.com/ljacqu/NightbotQuiz/archive/refs/heads/master.zip -o quiz-new.zip

echo "Extracting to quiz-new/"
unzip -q quiz-new.zip
mv NightbotQuiz-master/ quiz-new/

echo "Synchronizing generated files"
# No Git Bash support: rsync -a quiz/gen/ quiz-new/gen/
cp -rf quiz/gen/* quiz-new/gen/

echo "Replacing quiz/ with quiz-new/"
rm -rf quiz/
mv quiz-new/ quiz/

echo "Removing repo-only files"
rm quiz/.editorconfig
rm quiz/.gitattributes
rm quiz/.gitignore

echo "Deleting zip"
rm quiz-new.zip

echo "Update complete."
