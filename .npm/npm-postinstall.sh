rm -rf view/frontend/web/js/build
mkdir -p view/frontend/web/js/build

cp node_modules/node-forge/dist/* view/frontend/web/js/build/
cp node_modules/connect-sdk-client-js/dist/* view/frontend/web/js/build/
