

echo "# Generated file. Do not edit. Edit .env.docker instead" > .env
echo "" >> .env
cat .env.docker >> .env

eval $(grep -v '^#' .env)