package org.ywb.rest.domain;

import java.util.Date;

import org.codehaus.jackson.map.JsonSerializer;
import org.codehaus.jackson.map.annotate.JsonSerialize;

public class Fund {
	private String fundId;
	private String fundDescription;
	private double bidPrice;
	private double offerPrice;
	private Date lastUpdated;

	public Fund() {
	}

	/**
	 * Gets the fund id.
	 * 
	 * @return the fund id
	 */
	public String getFundId() {
		return fundId;
	}

	/**
	 * Sets the fund id.
	 * 
	 * @param fundId
	 *            the new fund id
	 */
	public void setFundId(String fundId) {
		this.fundId = fundId;
	}

	/**
	 * Gets the fund description.
	 * 
	 * @return the fund description
	 */
	public Object getFundDescription() {
		return fundDescription;
	}

	/**
	 * Sets the fund description.
	 * 
	 * @param fundDescription
	 *            the new fund description
	 */
	public void setFundDescription(String fundDescription) {
		this.fundDescription = fundDescription;
	}

	/**
	 * Gets the bid price.
	 * 
	 * @return the bid price
	 */
	public double getBidPrice() {
		return bidPrice;
	}

	/**
	 * Sets the bid price.
	 * 
	 * @param bidPrice
	 *            the new bid price
	 */
	public void setBidPrice(double bidPrice) {
		this.bidPrice = bidPrice;
	}

	/**
	 * Gets the offer price.
	 * 
	 * @return the offer price
	 */
	public double getOfferPrice() {
		return offerPrice;
	}

	/**
	 * Sets the offer price.
	 * 
	 * @param offerPrice
	 *            the new offer price
	 */
	public void setOfferPrice(double offerPrice) {
		this.offerPrice = offerPrice;
	}

	/**
	 * Gets the last updated.
	 * 
	 * @return the last updated
	 */
	@JsonSerialize(using=DateSerializer.class)
	public Date getLastUpdated() {
		return lastUpdated;
	}

	/**
	 * Sets the last updated.
	 * 
	 * @param lastUpdated
	 *            the new last updated
	 */
	public void setLastUpdated(Date lastUpdated) {
		this.lastUpdated = lastUpdated;
	}

	@Override
	public String toString() {
		return "Fund [fundId=" + fundId + ", fundDescription="
				+ fundDescription + ", bidPrice=" + bidPrice + ", offerPrice="
				+ offerPrice + ", lastUpdated=" + lastUpdated + "]";
	}
}
