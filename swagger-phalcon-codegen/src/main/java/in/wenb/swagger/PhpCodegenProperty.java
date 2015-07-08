package in.wenb.swagger;

import io.swagger.codegen.CodegenProperty;

import java.util.List;
import java.util.Map;

/**
 * Created by ywb on 15-7-7.
 */
public class PhpCodegenProperty extends CodegenProperty {
    List<Map<String, String>> validators;
    Boolean hasValidators;
}
