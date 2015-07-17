#!/bin/bash
dir=`dirname $0`
cd $dir/..

SWAGGER_CODEGEN_DIR=$HOME/src/others/swagger-codegen/modules
# JAVA_OPTS="-DdebugSwagger -DdebugModels -DdebugOperations -DdebugSupportingFiles"
JAVA_OPTS="-DdebugData"

java -cp $SWAGGER_CODEGEN_DIR/swagger-codegen-cli/target/swagger-codegen-cli.jar:$HOME/.m2/repository/com/alibaba/fastjson/1.1.41/fastjson-1.1.41.jar:target/classes $JAVA_OPTS io.swagger.codegen.SwaggerCodegen generate -l Phalcon -i /home/ywb/work/web/swagger/appanalyzer.yaml -o /tmp/api -c api-config.json -v
