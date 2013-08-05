package org.ywb.rest.controller;

import java.io.IOException;
import java.util.HashMap;
import java.util.Map;

import org.codehaus.jackson.JsonParseException;
import org.codehaus.jackson.map.JsonMappingException;
import org.codehaus.jackson.map.ObjectMapper;
import org.codehaus.jackson.map.type.TypeFactory;
import org.springframework.stereotype.Controller;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RequestParam;
import org.springframework.web.bind.annotation.ResponseBody;

@Controller
public class VersionController {

	private ObjectMapper objectMapper = new ObjectMapper();

	@RequestMapping(value = "/getversion")
	@ResponseBody
	public Map<String, String> getVersion(@RequestParam String params) throws JsonParseException, JsonMappingException, IOException {
		String clientVersion = readVersion(params);
		
		Map<String, String> ver = new HashMap<String, String>();
		ver.put("version", "2.1");
		return ver;
	}

	public String readVersion(String params) throws JsonParseException, JsonMappingException, IOException {
		Map<String, ParamVersion> param = objectMapper.readValue(params, TypeFactory.mapType(HashMap.class, String.class, ParamVersion.class));
		return param.get("params").getVersion();
	}
	
	private static class ParamVersion{
		private String version;

		public String getVersion() {
			return version;
		}

		@SuppressWarnings("unused")
		public void setVersion(String version) {
			this.version = version;
		}		
	}
}
