package org.ywb.rest.controller;

import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.stereotype.Controller;
import org.springframework.web.bind.annotation.PathVariable;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RequestMethod;
import org.springframework.web.servlet.ModelAndView;
import org.springframework.web.servlet.View;
import org.ywb.rest.domain.Fund;
import org.ywb.rest.service.FundService;

@Controller
public class FundController {

	@Autowired
	private FundService fundService_i;

	@Autowired
	private View jsonView_i;

	private static final String DATA_FIELD = "data";
	private static final String ERROR_FIELD = "error";
	
	public static boolean isEmpty(String s_p) {
		return (null == s_p) || s_p.trim().length() == 0;
	}

	/**
	 * Create an error REST response.
	 *
	 * @param sMessage
	 *            the s message
	 * @return the model and view
	 */
	private ModelAndView createErrorResponse(String sMessage) {
		return new ModelAndView(jsonView_i, ERROR_FIELD, sMessage);
	}

	@RequestMapping(value = "/rest/funds/{fundId}", method = RequestMethod.GET)
	public ModelAndView getFund(@PathVariable("fundId") String fundId_p) {
		Fund fund = null;

		/* validate fund Id parameter */
		if (isEmpty(fundId_p) || fundId_p.length() < 5) {
			String sMessage = "Error invoking getFund - Invalid fund Id parameter";
			return createErrorResponse(sMessage);
		}

		try {
			fund = fundService_i.getFundById(fundId_p);
		} catch (Exception e) {
			String sMessage = "Error invoking getFund. [%1$s]";
			return createErrorResponse(String.format(sMessage, e.toString()));
		}

		return new ModelAndView(jsonView_i, DATA_FIELD, fund);
	}
}
