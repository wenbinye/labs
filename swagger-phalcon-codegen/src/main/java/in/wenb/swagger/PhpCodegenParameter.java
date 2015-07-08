package in.wenb.swagger;

import io.swagger.codegen.CodegenParameter;

import java.util.List;
import java.util.Map;

/**
 * Created by ywb on 15-7-5.
 */
public class PhpCodegenParameter extends CodegenParameter {
    /**
     * maxLength validation for strings, see http://json-schema.org/latest/json-schema-validation.html#rfc.section.5.2.1
     */
    public Integer maxLength;
    /**
     * minLength validation for strings, see http://json-schema.org/latest/json-schema-validation.html#rfc.section.5.2.2
     */
    public Integer minLength;
    /**
     * pattern validation for strings, see http://json-schema.org/latest/json-schema-validation.html#rfc.section.5.2.3
     */
    public String pattern;

    public Double minimum;
    public Double maximum;
    public Boolean exclusiveMinimum;
    public Boolean exclusiveMaximum;
    public boolean isEnum;
    public List<String> _enum;
    public Map<String, Object> allowableValues;
}
