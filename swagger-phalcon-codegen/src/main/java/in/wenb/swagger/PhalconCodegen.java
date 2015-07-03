package in.wenb.swagger;

import java.io.File;
import java.io.FileOutputStream;
import java.io.IOException;
import java.lang.reflect.Field;
import java.util.*;

import io.swagger.codegen.*;
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

    private Map<String, Map<String, Object>> modelContext = new HashMap<String, Map<String, Object>>();

    private Map<String, Map<String, Object>> operationContext = new HashMap<String, Map<String, Object>>();

    private boolean initAdditionalProperties = false;
    protected String sourceFolder = "src";

    public PhalconCodegen() {
        super();
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
        return "Generates a Phalcon client library.";
    }

    @Override
    public Map<String, Object> postProcessModels(Map<String, Object> objs) {
        objs = super.postProcessModels(objs);
        @SuppressWarnings("unchecked")
        Map<String, Object> modelInfo = (Map<String, Object>) ((List<Object>) objs.get("models")).get(0);
        processModel(modelInfo);
        CodegenModel model = (CodegenModel) modelInfo.get("model");
        modelContext.put(model.name, objs);
        return objs;
    }

    @Override
    public String toModelFilename(String name) {
        if (System.getProperty("debugData") != null) {
            try {
                String modelName = camelize(name);
                if (modelContext.containsKey(modelName)) {

                    File modelDataFile = new File("sample/models/" + modelName + ".json");
                    if (!modelDataFile.getParentFile().exists()) {
                        modelDataFile.getParentFile().mkdirs();
                    }
                    logger.info("create data file {}", modelDataFile);
                    IOUtils.write(Json.pretty(modelContext.get(modelName)), new FileOutputStream(modelDataFile));
                }
            } catch (IOException e) {
                logger.error("", e);
            }
        }
        return super.toModelFilename(name);
    }

    @Override
    public Map<String, Object> postProcessOperations(Map<String, Object> objs) {
        objs = super.postProcessOperations(objs);
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
            processOperation(operation);
        }
        operationContext.put((String) operationInfo.get("classname"), objs);
        return objs;
    }

    @Override
    public String apiFilename(String templateName, String tag) {
        if (System.getProperty("debugData") != null) {
            try {
                String apiName = toApiName(tag);
                if (operationContext.containsKey(apiName)) {
                    File apiDataFile = new File("sample/apis/" + camelize(tag) + ".json");
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
        return super.apiFilename(templateName, tag);
    }

    private void processOperation(CodegenOperation operation) {
        for (CodegenParameter parameter: operation.allParams) {
            for (Field field: parameter.getClass().getDeclaredFields()) {
                try {
                    if (field.getType() == Boolean.class && field.get(parameter) == null) {
                        field.set(parameter, false);
                    }
                } catch (IllegalAccessException e) {
                    logger.error("", e);
                }
            }
        }
    }

    private void processModel(Map<String, Object> modelInfo) {
        CodegenModel model = (CodegenModel) modelInfo.get("model");
        if (Strings.isNullOrEmpty(model.description)) {
            model.description = "The model " + model.name + ".";
        }
        for (CodegenProperty property: model.vars) {
            processProperty(property);
        }
    }

    private void processProperty(CodegenProperty property) {
        if (Strings.isNullOrEmpty(property.description)) {
            property.description = "The " + property.name + ".";
        }
        List<String> params = new ArrayList<String>();
        if (property.required!=null) {
            params.add("required=" + property.required);
        }
        if (INT_TYPES.contains(property.datatype)) {
            params.add("type=integer");
        } else if (NUM_TYPES.contains(property.datatype)) {
            params.add("type=number");
        } else if ("boolean".equals(property.datatype)) {
            params.add("type=boolean");
        } else if ("array".equals(property.baseType)) {
            params.add("type=array");
            if (property.complexType != null) {
                params.add("element=" + property.complexType);
            }
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
            params.add("validator=Range(" + joiner.join(validatorParams) + ")");
        }
        if (!params.isEmpty()) {
            String validator = "@Valid(" + joiner.join(params) + ")";
            property.description = property.description + "\n     * " + "\n     * " + validator;
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
    public String toApiName(String name) {
        return camelize(name) + "Controller";
    }

    @Override
    public String apiFileFolder() {
        return outputFolder + File.separatorChar + sourceFolder + File.separatorChar
                + apiPackage.replace(NAMESPACE_SEPARATOR, File.separatorChar);
    }

    @Override
    public String toModelImport(String name) {
        if ("array".equals(name)) {
            return "";
        }
        return modelPackage() + NAMESPACE_SEPARATOR + name;
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
}