#!/bin/bash

dir=`dirname $0`
cd $dir/..

SWAGGER_CODEGEN_DIR=$HOME/src/others/swagger-codegen/modules
# JAVA_OPTS="-DdebugSwagger -DdebugModels -DdebugOperations -DdebugSupportingFiles"
JAVA_OPTS=

java -cp $SWAGGER_CODEGEN_DIR/swagger-codegen-cli/target/swagger-codegen-cli.jar:$HOME/.m2/repository/com/alibaba/fastjson/1.1.41/fastjson-1.1.41.jar:target/classes $JAVA_OPTS in.wenb.swagger.MustacheRender $@
