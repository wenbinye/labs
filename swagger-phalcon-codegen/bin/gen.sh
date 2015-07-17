#!/bin/bash
dir=`dirname $0`
cd $dir/..

SWAGGER_CODEGEN_DIR=$HOME/src/others/swagger-codegen/modules
# JAVA_OPTS="-DdebugSwagger -DdebugModels -DdebugOperations -DdebugSupportingFiles"
JAVA_OPTS="-DdebugData"

echo java -cp $SWAGGER_CODEGEN_DIR/swagger-codegen-cli/target/swagger-codegen-cli.jar:$HOME/.m2/repository/com/alibaba/fastjson/1.1.41/fastjson-1.1.41.jar:target/classes $JAVA_OPTS io.swagger.codegen.SwaggerCodegen generate -l Phalcon -i $SWAGGER_CODEGEN_DIR/swagger-codegen/src/test/resources/2_0/petstore.json -o sample -c config.json -v
