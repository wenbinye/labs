/**
 * Alipay.com Inc.
 * Copyright (c) 2004-2015 All Rights Reserved.
 */
package in.wenb.swagger;

import java.io.File;
import java.io.FileInputStream;
import java.io.FileNotFoundException;
import java.io.FileReader;
import java.io.IOException;
import java.io.InputStream;
import java.io.InputStreamReader;
import java.io.Reader;

import org.apache.commons.io.IOUtils;

import com.alibaba.fastjson.JSON;
import com.samskivert.mustache.Mustache;
import com.samskivert.mustache.Template;
import io.swagger.util.Json;

/**
 *
 *
 * @author dabing.ywb
 * @version $Id: MustacheRender.java, v 0.1 2015年07月02日 下午8:35:00 dabing.ywb Exp $
 */
public class MustacheRender {
    public static void main(String[] args) {
        String templateFile = args[0];
        String modelFile = args[1];
        new MustacheRender().run(templateFile, modelFile);
    }

    public void run(final String templateFile, String modelFile) {
        File file = new File(templateFile);
        final File dir = file.getParentFile();
        String template = readTemplate(templateFile);
        Template tmpl = Mustache.compiler()
                .withLoader(new Mustache.TemplateLoader() {
                    public Reader getTemplate(String name) {
                        try {
                            return new FileReader(new File(dir + name));
                        } catch (FileNotFoundException e) {
                            e.printStackTrace();
                            return null;
                        }
                    }
                })
                .defaultValue("")
                .compile(template);
        try {
            Object data = JSON.parse(IOUtils.toString(new FileInputStream(new File(modelFile))));
            System.out.println(tmpl.execute(data));
        } catch (IOException e) {
            e.printStackTrace();
        }
    }


    public String readTemplate(String name) {
        try {
            Reader reader = new FileReader(name);
            if (reader == null) {
                throw new RuntimeException("no file found");
            }
            java.util.Scanner s = new java.util.Scanner(reader).useDelimiter("\\A");
            return s.hasNext() ? s.next() : "";
        } catch (Exception e) {
            e.printStackTrace();
        }
        throw new RuntimeException("can't load template " + name);
    }

}
