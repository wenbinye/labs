package in.wenb.swagger;

import java.util.Arrays;
import java.util.Collection;

import org.junit.Test;
import org.junit.runner.RunWith;
import org.junit.runners.Parameterized;

import static org.hamcrest.CoreMatchers.is;
import static org.junit.Assert.assertThat;

/**
 *
 * @author dabing.ywb
 * @version $Id$
 */
@RunWith(Parameterized.class)
public class PhalconCodegenVersionTest {
    @Parameterized.Parameters
    public static Collection<Object[]> data() {
        return Arrays.asList(new Object[][]{
                {"1.0.0", "V1000"},
                {"1.2.0", "V1002"},
                {"1.2.320", "V1002"},
                {"1", "V1"}
        });
    }

    PhalconCodegen config = new PhalconCodegen();

    private String version;
    private String namespaceVersion;

    public PhalconCodegenVersionTest(String version, String namespaceVersion) {
        this.version = version;
        this.namespaceVersion = namespaceVersion;
    }

    @Test
    public void testModelPackage() throws Exception {
        config.additionalProperties().put("version", version);
        assertThat(config.modelPackage(), is(namespaceVersion));
    }
}