package in.wenb.swagger;

import com.alibaba.fastjson.JSON;
import com.alibaba.fastjson.serializer.SerializerFeature;
import com.google.common.base.Joiner;
import com.google.common.base.Preconditions;
import com.google.common.base.Splitter;
import com.google.common.base.Strings;
import com.google.common.collect.Maps;
import com.google.common.collect.Sets;
import io.swagger.codegen.*;
import io.swagger.codegen.languages.PhpClientCodegen;
import io.swagger.models.ArrayModel;
import io.swagger.models.Swagger;
import io.swagger.models.parameters.AbstractSerializableParameter;
import io.swagger.models.parameters.BodyParameter;
import io.swagger.models.parameters.Parameter;
import io.swagger.models.properties.*;
import org.apache.commons.io.IOUtils;
import org.apache.commons.lang.StringUtils;
import org.slf4j.Logger;
import org.slf4j.LoggerFactory;

import java.io.File;
import java.io.FileOutputStream;
import java.io.IOException;
import java.util.*;

public class PhalconCodegen extends PhpClientCodegen implements CodegenConfig {
    private static final Logger logger = LoggerFactory.getLogger(PhalconCodegen.class);

    public static final char NAMESPACE_SEPARATOR = '\\';
    public static final String NAMESPACE = "namespace";

    public static final Set<String> INT_TYPES = Sets.newHashSet("integer", "long", "int");

    public static final Set<String> NUM_TYPES = Sets.newHashSet("double", "float", "number");
    public static final String BOOLEAN_TYPE = "boolean";
    public static final String ARRAY_TYPE = "array";

    private Map<String, Map<String, Object>> modelContext = new HashMap<String, Map<String, Object>>();

    private Map<String, Map<String, Object>> operationContext = new HashMap<String, Map<String, Object>>();

    private static final Map<String, String> phpValidators = new HashMap<String, String>();

    private static final Set<String> excludedImports = new HashSet<String>();

    private boolean initAdditionalProperties = false;
    protected String sourceFolder = "src";

    static {
        phpValidators.put("IsA", "PhalconX\\Validators\\IsA");
        phpValidators.put("Range", "PhalconX\\Validators\\Range");
        phpValidators.put("Inclusionin", "Phalcon\\Validation\\Validator\\Inclusionin");

        excludedImports.add("map");
        excludedImports.add("array");
    }

    public PhalconCodegen() {
        super();

        CodegenModelFactory.setTypeMapping(CodegenModelType.PARAMETER, PhpCodegenParameter.class);
        CodegenModelFactory.setTypeMapping(CodegenModelType.OPERATION, PhpCodegenOperation.class);
        CodegenModelFactory.setTypeMapping(CodegenModelType.PROPERTY, PhpCodegenProperty.class);

        invokerPackage = "Swagger";
        /**
         * Template Location.  This is the location which templates will be read from.  The generator
         * will use the resource stream to attempt to read the templates.
         */
        templateDir = "Phalcon";
        /**
         * Supporting Files.  You can write single files for the generator with the
         * entire object tree available.  If the input file has a suffix of `.mustache
         * it will be processed by the template engine.  Otherwise, it will be copied
         */
        supportingFiles = new ArrayList<SupportingFile>();

        apiTemplateFiles.clear();
        apiTemplateFiles.put("controller.mustache", ".php");
        apiTemplateFiles.put("api.mustache", ".php");
        apiTemplateFiles.put("service.mustache", ".php");

        cliOptions.add(new CliOption(NAMESPACE, "Code namespace"));
    }

    /**
     * Configures the type of generator.
     *
     * @return the CodegenType for this generator
     * @see     io.swagger.codegen.CodegenType
     */
    public CodegenType getTag() {
        return CodegenType.SERVER;
    }

    /**
     * Configures a friendly name for the generator.  This will be used by the generator
     * to select the library with the -l flag.
     *
     * @return the friendly name for the generator
     */
    public String getName() {
        return "Phalcon";
    }

    /**
     * Returns human-friendly help for the generator.  Provide the consumer with help
     * tips, parameters here
     *
     * @return A string value for the help message
     */
    public String getHelp() {
        return "Generates a Phalcon server library.";
    }

    @Override
    public Map<String, Object> postProcessModels(Map<String, Object> objs) {
        objs = super.postProcessModels(objs);
        @SuppressWarnings("unchecked")
        Map<String, Object> modelInfo = (Map<String, Object>) ((List<Object>) objs.get("models")).get(0);
        processModel(modelInfo, objs);
        CodegenModel model = (CodegenModel) modelInfo.get("model");
        modelContext.put(model.name, objs);
        return objs;
    }

    @Override
    public Map<String, Object> postProcessOperations(Map<String, Object> objs) {
        objs = super.postProcessOperations(objs);
        @SuppressWarnings("unchecked")
        List<Map<String, String>> imports = (List<Map<String, String>>) objs.get("imports");
        @SuppressWarnings("unchecked")
        Map<String, Object> operationInfo = ((Map<String, Object>) objs.get("operations"));
        @SuppressWarnings("unchecked")
        List<CodegenOperation> operations = (List<CodegenOperation>) operationInfo.get("operation");
        List<Map<String, String>> validatorImports = new ArrayList<Map<String, String>>();

        for (CodegenOperation operation: operations) {
            processOperation((PhpCodegenOperation) operation, validatorImports);
        }
        operationContext.put(((String) operationInfo.get("classname")).replace("Controller", ""), objs);
        objs.put("imports", filterImports(imports));
        objs.put("validatorImports", filterImports(validatorImports));
        return objs;
    }

    private void processOperation(PhpCodegenOperation operation, List<Map<String, String>> imports) {
        operation.httpMethod = camelize(StringUtils.lowerCase(operation.httpMethod));
        List<String> validators = new ArrayList<String>();
        int baseIndent = 8;
        Joiner joiner = Joiner.on(",\n" + Strings.repeat(" ", baseIndent + 8));
        for (CodegenParameter parameter: operation.allParams) {
            PhpCodegenParameter p = (PhpCodegenParameter) parameter;
            List<String> params = new ArrayList<String>();
            params.add("'name' => '" + p.paramName + "'");
            if (parameter.isBodyParam != null && parameter.isBodyParam) {
                createBodyParameterValidator(p, params, imports);
            } else {
                createParameterValidator(p, params, imports);
            }

            // name, value are default element
            if (params.size() > 2) {
                validators.add("[\n" + Strings.repeat(" ", baseIndent + 8) + joiner.join(params)
                        + "\n" + Strings.repeat(" ", baseIndent + 4) + "]");
            }
        }
        if (!validators.isEmpty()) {
            String indent = Strings.repeat(" ", baseIndent+4);
            operation.validators = "[\n" + indent
                    + Joiner.on(",\n" + indent).join(validators)
                    + "\n" + Strings.repeat(" ", baseIndent) + "]";
            operation.hasValidators = true;
        }
    }

    private void createParameterValidator(PhpCodegenParameter parameter, List<String> params, List<Map<String, String>> imports) {
        if (parameter.defaultValue != null) {
            params.add("'value' => &$" + parameter.paramName);
            params.add("'default' => '" + parameter.defaultValue.replaceAll("'", "\\'") + "'");
        } else {
            params.add("'value' => $" + parameter.paramName);
        }
        if (INT_TYPES.contains(parameter.dataType)) {
            params.add("'type' => 'integer'");
        } else if (NUM_TYPES.contains(parameter.dataType)) {
            params.add("'type' => 'number'");
        } else if (BOOLEAN_TYPE.equals(parameter.dataType)) {
            params.add("'type' => 'boolean'");
        } else if (parameter.isContainer != null && parameter.isContainer) {
            params.add("'type' => 'array'");
        }
        if (parameter.required != null && parameter.required) {
            params.add("'required' => true");
        }
        if (parameter.minimum != null || parameter.maximum != null) {
            List<String> validatorParams = new ArrayList<String>();
            if (parameter.minimum != null) {
                validatorParams.add("'minimum' => " + parameter.minimum);
            }
            if (parameter.maximum != null) {
                validatorParams.add("'maximum' => " + parameter.maximum);
            }
            if (parameter.exclusiveMinimum != null) {
                validatorParams.add("'exclusiveMinimum' => " + parameter.exclusiveMinimum);
            }
            if (parameter.exclusiveMaximum != null) {
                validatorParams.add("'exclusiveMaximum' => " + parameter.exclusiveMaximum);
            }
            params.add("'validator' => new Range([" + Joiner.on(", ").join(validatorParams) + "])");
            imports.add(createSubElement("import", phpValidators.get("Range")));
        }
    }

    private void createBodyParameterValidator(CodegenParameter parameter, List<String> params, List<Map<String, String>> imports) {
        params.add("'value' => $" + parameter.paramName);
        if (parameter.required != null && parameter.required) {
            params.add("'required' => true");
        }
        String validator = "validator";
        String dataType = parameter.dataType;
        if (parameter.isContainer != null && parameter.isContainer) {
            params.add("'type' => 'array'");
            validator = "element";
            dataType = parameter.baseType;
        }
        params.add("'"+validator+"' => new IsA(['class' => " + dataType + "::CLASS])");
        imports.add(createSubElement("import", phpValidators.get("IsA")));
    }

    private Map<String,String> createSubElement(String key, String content) {
        Map<String, String> elem = Maps.newHashMap();
        elem.put(key, content);
        return elem;
    }

    @Override
    public CodegenParameter fromParameter(Parameter param, Set<String> imports) {
        PhpCodegenParameter parameter = (PhpCodegenParameter) super.fromParameter(param, imports);
        if (param instanceof AbstractSerializableParameter) {
            AbstractSerializableParameter queryParameter = (AbstractSerializableParameter) param;
            Property property = PropertyBuilder.build(queryParameter.getType(), queryParameter.getFormat(), null);
            if (property instanceof AbstractNumericProperty) {
                parameter.maximum = queryParameter.getMaximum();
                parameter.minimum = queryParameter.getMinimum();
                parameter.exclusiveMaximum = queryParameter.isExclusiveMaximum();
                parameter.exclusiveMinimum = queryParameter.isExclusiveMinimum();
            } else if (property instanceof ArrayProperty) {
                parameter.baseType = ARRAY_TYPE;
            }
        } else if (param instanceof BodyParameter && parameter.isContainer != null && parameter.isContainer) {
            BodyParameter bp = (BodyParameter) param;
            ArrayModel schema = (ArrayModel) bp.getSchema();
            parameter.baseType = ((RefProperty) schema.getItems()).getSimpleRef();
        }
        return parameter;
    }

    private void processModel(Map<String, Object> modelInfo, Map<String, Object> objs) {
        CodegenModel model = (CodegenModel) modelInfo.get("model");
        if (Strings.isNullOrEmpty(model.description)) {
            model.description = "The model " + model.name + ".";
        }
        @SuppressWarnings("unchecked")
        List<Map<String, String>> imports = (List<Map<String, String>>) objs.get("imports");
        for (CodegenProperty property: model.vars) {
            processProperty((PhpCodegenProperty) property, imports);
        }
        objs.put("imports", filterImports(imports));
    }

    private List<Map<String, String>> filterImports(List<Map<String, String>> imports) {
        List<Map<String, String>> filtered = new ArrayList<Map<String, String>>();
        Set<String> see = new HashSet<String>();
        for (Map<String, String> pkg: imports) {
            String importPkg = pkg.get("import");
            if (!Strings.isNullOrEmpty(importPkg) && !excludedImports.contains(importPkg)
                    && !see.contains(importPkg)) {
                filtered.add(pkg);
                see.add(importPkg);
            }
        }
        return filtered;
    }

    private void processProperty(PhpCodegenProperty property, List<Map<String, String>> imports) {
        if (Strings.isNullOrEmpty(property.description)) {
            property.description = "The " + property.name + ".";
        }
        List<Map<String, String>> validators = new ArrayList<Map<String, String>>();
        List<String> params = new ArrayList<String>();
        if (property.required!=null) {
            params.add("required=" + property.required);
        }
        if (INT_TYPES.contains(property.datatype)) {
            params.add("type=integer");
        } else if (NUM_TYPES.contains(property.datatype)) {
            params.add("type=number");
        } else if (BOOLEAN_TYPE.equals(property.datatype)) {
            params.add("type=boolean");
        } else if (property.isContainer != null && property.isContainer) {
            params.add("type=array");
            if (property.complexType != null) {
                validators.add(createSubElement("validator", "@IsA(\"" + property.complexType + "[]\")"));
                imports.add(createSubElement("import", phpValidators.get("IsA")));
            } else {
                params.add("type=array");
            }
        } else if (property.complexType != null) {
            validators.add(createSubElement("validator", "@IsA(" + property.complexType + ")"));
            imports.add(createSubElement("import", phpValidators.get("IsA")));
        }
        if (property.defaultValue != null && !"null".equals(property.defaultValue)) {
            params.add("default=\"" + property.defaultValue+ "\"");
        }
        Joiner joiner = Joiner.on(", ");
        if (property.minimum != null || property.maximum != null) {
            List<String> validatorParams = new ArrayList<String>();
            if (property.minimum != null) {
                validatorParams.add("minimum=" + property.minimum);
            }
            if (property.maximum != null) {
                validatorParams.add("maximum=" + property.maximum);
            }
            if (property.exclusiveMinimum != null) {
                validatorParams.add("exclusiveMinimum=" + property.exclusiveMinimum);
            }
            if (property.exclusiveMaximum != null) {
                validatorParams.add("exclusiveMaximum=" + property.exclusiveMaximum);
            }
            params.add("validator=@Range(" + joiner.join(validatorParams) + ")");
            imports.add(createSubElement("import", phpValidators.get("Range")));
        }
        if (property.isEnum) {
            params.add("validator=@Inclusionin(domain=["+joiner.join(property._enum)+"])");
            imports.add(createSubElement("import", phpValidators.get("Inclusionin")));
        }
        if (!params.isEmpty()) {
            validators.add(createSubElement("validator", "@Valid(" + joiner.join(params) + ")"));
        }
        if (!validators.isEmpty()) {
            property.validators = validators;
            property.hasValidators = true;
        }
    }

    private void initAdditionalProperties() {
        if (this.initAdditionalProperties) {
            return;
        }
        this.initAdditionalProperties = true;
        if (additionalProperties.containsKey(NAMESPACE)) {
            invokerPackage = (String) additionalProperties.get(NAMESPACE);
        }
        invokerPackage = invokerPackage + NAMESPACE_SEPARATOR + getNamespaceVersion();
        additionalProperties.put("invokerPackage", invokerPackage);
        modelPackage = invokerPackage + NAMESPACE_SEPARATOR + "Models";
        apiPackage = invokerPackage + NAMESPACE_SEPARATOR + "Controllers";

        supportingFiles.add(new SupportingFile("BaseController.mustache",
                sourceFolder + File.separatorChar + (invokerPackage + NAMESPACE_SEPARATOR + "Controllers").replace(NAMESPACE_SEPARATOR, File.separatorChar),
                "BaseController.php"));
    }

    @Override
    public String modelPackage() {
        initAdditionalProperties();
        return super.modelPackage();
    }

    @Override
    public String modelFileFolder() {
        return outputFolder + File.separatorChar + sourceFolder + File.separatorChar
                + modelPackage.replace(NAMESPACE_SEPARATOR, File.separatorChar);
    }

    @Override
    public String toModelImport(String name) {
        if (excludedImports.contains(name)) {
            return "";
        }
        return modelPackage() + NAMESPACE_SEPARATOR + name;
    }

    @Override
    public String toApiName(String name) {
        return name.length() == 0 ? "IndexController" : this.initialCaps(name) + "Controller";
    }

    @Override
    public String apiFilename(String templateName, String tag) {
        String type = camelize(templateName.replace(".mustache", ""));
        String pkg = invokerPackage + NAMESPACE_SEPARATOR + type+"s";
        String suffix = apiTemplateFiles.get(templateName);
        return outputFolder + File.separatorChar + sourceFolder + File.separatorChar
                    + pkg.replace(NAMESPACE_SEPARATOR, File.separatorChar)
                + File.separatorChar + camelize(tag) + type + suffix;
    }

    private String getNamespaceVersion() {
        return formatNamespaceVersion((String) additionalProperties.get("version"));
    }

    private String formatNamespaceVersion(String version) {
        Preconditions.checkArgument(!Strings.isNullOrEmpty(version));
        List<String> versions = Splitter.on(".").splitToList(version);
        if (versions.size()>=2) {
            return String.format("V%s", versions.get(0));
        } else {
            return "V" + versions.get(0);
        }
    }

    @Override
    public void processSwagger(Swagger swagger) {
        super.processSwagger(swagger);
        if (System.getProperty("debugData") != null) {
            saveVars();
        }
    }

    private void saveVars() {
        try {
            saveVar(modelContext, "models");
            saveVar(operationContext, "apis");
        } catch (IOException e) {
            logger.error("", e);
        }
    }

    private void saveVar(Map<String, ?> vars, String path) throws IOException {
        for (String varName: vars.keySet()) {
            File outfile = new File(outputFolder, path + "/" + varName + ".json");
            if (!outfile.getParentFile().exists() && !outfile.getParentFile().mkdirs()) {
                throw new IOException("Cannot create directory for file " + outfile);
            }
            logger.info("create data file {}", outfile);
            IOUtils.write(JSON.toJSONString(vars.get(varName), SerializerFeature.PrettyFormat), new FileOutputStream(outfile));
        }
    }
}
