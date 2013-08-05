package org.ywb.rest.service;

import java.util.Date;

import org.springframework.stereotype.Service;
import org.ywb.rest.domain.Fund;

@Service
public class FundService {

	public Fund getFundById(String fundId_p) {
		Fund fund = new Fund();

		fund.setFundId(fundId_p);
		fund.setFundDescription("High Risk Equity Fund");
		fund.setBidPrice(26.80);
		fund.setOfferPrice(27.40);
		fund.setLastUpdated(new Date());

		return fund;
	}
}
