package org.ywb.rest.controller;

import static org.junit.Assert.assertEquals;

import java.io.IOException;
import java.util.Map;

import org.codehaus.jackson.JsonParseException;
import org.codehaus.jackson.map.JsonMappingException;
import org.junit.Test;
import org.springframework.beans.factory.annotation.Autowired;

public class VersionControllerTest extends AbstractControllerTest{
	@Autowired
	private VersionController versionCtl;
	
	private String params = "{\"params\": {\"version\": \"2.0\"}}";
	
	@Test
	public void testGetVersion() throws JsonParseException, JsonMappingException, IOException {
		Map<String,String> result = versionCtl.getVersion(params);
		assertEquals("2.1", result.get("version"));
	}
	
	@Test
	public void testReadVersion() throws JsonParseException, JsonMappingException, IOException{
		String ver = versionCtl.readVersion(params);
		assertEquals("2.0", ver);
	}
}
