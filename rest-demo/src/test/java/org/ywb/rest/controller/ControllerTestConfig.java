package org.ywb.rest.controller;

import org.springframework.context.annotation.ComponentScan;
import org.springframework.context.annotation.Configuration;
import org.springframework.context.annotation.ImportResource;

@Configuration
@ImportResource(value = "classpath:spring/servlet-context.xml")
@ComponentScan(value = "org.ywb.rest")
public class ControllerTestConfig {

}
