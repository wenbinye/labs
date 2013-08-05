package org.ywb.rest.test;

public class DataTest {
	/**
	 * @param args
	 */
	public static void main(String[] args) {
		String sep = "\01";
		String str = "abc\01def";
		for (String part : str.split(sep))
			System.out.println(part);
	}
}
