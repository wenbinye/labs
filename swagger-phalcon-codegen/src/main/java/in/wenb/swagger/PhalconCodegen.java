package in.wenb.swagger;

import java.io.File;
import java.io.FileOutputStream;
import java.io.IOException;
import java.util.*;

import com.google.common.collect.Maps;
import io.swagger.codegen.*;
import io.swagger.models.ArrayModel;
import io.swagger.models.Swagger;
import io.swagger.models.parameters.AbstractSerializableParameter;
import io.swagger.models.parameters.BodyParameter;
import io.swagger.models.parameters.Parameter;
import io.swagger.models.properties.*;
import org.apache.commons.io.IOUtils;
import org.slf4j.Logger;
import org.slf4j.LoggerFactory;

import com.google.common.base.Joiner;
import com.google.common.base.Preconditions;
import com.google.common.base.Splitter;
import com.google.common.base.Strings;
import com.google.common.collect.Sets;
import io.swagger.codegen.languages.PhpClientCodegen;
import io.swagger.util.Json;

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

    private boolean initAdditionalProperties = false;
    protected String sourceFolder = "src";

    static {
        phpValidators.put("IsA", "PhalconX\\Validators\\IsA");
        phpValidators.put("Range", "PhalconX\\Validators\\Range");
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
        objs.put("validatorImports", new ArrayList<Object>());
        List<Map<String, String>> imports = (List<Map<String, String>>) objs.get("imports");
        Iterator<Map<String, String>> it = imports.iterator();
        while (it.hasNext()) {
            Map<String, String> oneImport = it.next();
            if (Strings.isNullOrEmpty(oneImport.get("import"))) {
                it.remove();
            }
        }
        Map<String, Object> operationInfo = ((Map<String, Object>) objs.get("operations"));
        List<CodegenOperation> operations = (List<CodegenOperation>) operationInfo.get("operation");
        for (CodegenOperation operation: operations) {
            processOperation((PhpCodegenOperation) operation, objs);
        }
        operationContext.put((String) operationInfo.get("baseName"), objs);
        return objs;
    }

    private void processOperation(PhpCodegenOperation operation, Map<String, Object> objs) {
        List<Object> imports = (List<Object>) objs.get("validatorImports");
        List<Map<String, String>> validators = new ArrayList<Map<String, String>>();
        List<String> otherValidators = new ArrayList<String>();
        int baseIndent = 8;
        for (CodegenParameter parameter: operation.allParams) {
            if (parameter.isBodyParam == Boolean.TRUE) {
                createBodyParameterValidator(parameter);
            }
            PhpCodegenParameter p = (PhpCodegenParameter) parameter;
            List<String> params = new ArrayList<String>();
            params.add("'name' => '" + p.paramName + "'");
            if (p.defaultValue != null) {
                params.add("'value' => &$" + p.paramName);
                params.add("'default' => '" + p.defaultValue.replaceAll("'", "\\'") + "'");
            } else {
                params.add("'value' => $" + p.paramName);
            }
            if (INT_TYPES.contains(p.dataType)) {
                params.add("'type' => 'integer'");
            } else if (NUM_TYPES.contains(p.dataType)) {
                params.add("'type' => 'number'");
            } else if (BOOLEAN_TYPE.equals(p.dataType)) {
                params.add("'type' => 'boolean'");
            } else if (ARRAY_TYPE.equals(p.baseType)) {
                params.add("'type' => 'array'");
            }
            if (p.required == Boolean.TRUE) {
                params.add("'required' => true");
            }
            Joiner joiner = Joiner.on(",\n" + Strings.repeat(" ", baseIndent + 8));
            if (p.minimum != null || p.maximum != null) {
                List<String> validatorParams = new ArrayList<String>();
                if (p.minimum != null) {
                    validatorParams.add("'minimum' => " + p.minimum);
                }
                if (p.maximum != null) {
                    validatorParams.add("'maximum' => " + p.maximum);
                }
                if (p.exclusiveMinimum != null) {
                    validatorParams.add("'exclusiveMinimum' => " + p.exclusiveMinimum);
                }
                if (p.exclusiveMaximum != null) {
                    validatorParams.add("'exclusiveMaximum' => " + p.exclusiveMaximum);
                }
                params.add("'validator' => new Range([" + joiner.join(validatorParams) + "])");
                imports.add(createSubElement("import", phpValidators.get("Range")));
            }
            if (params.size() > 2) {
                otherValidators.add("[\n" + Strings.repeat(" ", baseIndent + 8) + joiner.join(params)
                        + "\n" + Strings.repeat(" ", baseIndent+4) +"]");
            }
        }
        if (!otherValidators.isEmpty()) {
            String indent = Strings.repeat(" ", baseIndent+4);
            validators.add(createSubElement("validator", "[\n" + indent
                    + Joiner.on(",\n" + indent).join(otherValidators)
                    + "\n"+Strings.repeat(" ", baseIndent)+"]"));
        }
        operation.validators = validators;
    }

    private void createBodyParameterValidator(CodegenParameter parameter) {
        List<String> params = new ArrayList<String>();
        params.add("'value' => $" + parameter.paramName);
        if (parameter.required == Boolean.TRUE) {
            params.add("'required' => true");
        }
        return;
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
        } else if (param instanceof BodyParameter && parameter.isContainer == Boolean.TRUE) {
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
        List<Object> imports = (List<Object>) objs.get("imports");
        for (CodegenProperty property: model.vars) {
            processProperty((PhpCodegenProperty) property, imports);
        }
    }

    private void processProperty(PhpCodegenProperty property, List<Object> imports) {
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
        } else if (property.isContainer == Boolean.TRUE) {
            params.add("type=array");
            if (property.complexType != null) {
                validators.add(createSubElement("validator", "@IsA(\"" + property.complexType + "[]\")"));
                imports.add(createSubElement("import", phpValidators.get("IsA")));
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
        if (!params.isEmpty()) {
            validators.add(createSubElement("validator", "@Valid(" + joiner.join(params) + ")"));
        }
        if (!validators.isEmpty()) {
            property.validators = validators;
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
        if (ARRAY_TYPE.equals(name)) {
            return "";
        }
        return modelPackage() + NAMESPACE_SEPARATOR + name;
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
            return String.format("V%s%03d", versions.get(0), Integer.parseInt(versions.get(1)));
        } else {
            return "V" + versions.get(0);
        }
    }

    @Override
    public void processSwagger(Swagger swagger) {
        super.processSwagger(swagger);
        if (System.getProperty("debugData") != null) {
            try {
                for (String modelName: modelContext.keySet()) {
                    File modelDataFile = new File(outputFolder, "models/" + modelName + ".json");
                    if (!modelDataFile.getParentFile().exists()) {
                        modelDataFile.getParentFile().mkdirs();
                    }
                    logger.info("create data file {}", modelDataFile);
                    IOUtils.write(Json.pretty(modelContext.get(modelName)), new FileOutputStream(modelDataFile));
                }
                for (String apiName: operationContext.keySet()) {
                    File apiDataFile = new File(outputFolder, "apis/" + apiName + ".json");
                    if (!apiDataFile.getParentFile().exists()) {
                        apiDataFile.getParentFile().mkdirs();
                    }
                    logger.info("create data file {}", apiDataFile);
                    IOUtils.write(Json.pretty(operationContext.get(apiName)), new FileOutputStream(apiDataFile));
                }
            } catch (IOException e) {
                logger.error("", e);
            }
        }
    }
}