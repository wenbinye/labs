package org.ywb.rest.test;

import org.slf4j.Logger;
import org.slf4j.LoggerFactory;

public class LogDemo {
	static final Logger logger = LoggerFactory.getLogger(LogDemo.class);
	
	public static void main(String[] args) {
		logger.trace("Hello World!");
		logger.debug("How are you today?");
		logger.info("I am fine.");
		logger.warn("I love programming.");
		logger.error("I am programming.");
	}
}
