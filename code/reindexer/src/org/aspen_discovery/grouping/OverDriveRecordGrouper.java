package org.aspen_discovery.grouping;

import com.turning_leaf_technologies.indexing.RecordIdentifier;
import com.turning_leaf_technologies.logging.BaseIndexingLogEntry;
import org.apache.logging.log4j.Logger;
import org.json.JSONArray;
import org.json.JSONException;
import org.json.JSONObject;

import java.nio.charset.StandardCharsets;
import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.util.Locale;
import java.util.regex.Pattern;

public class OverDriveRecordGrouper extends RecordGroupingProcessor {
	private PreparedStatement getOverDriveProductInfoStmt;
	private PreparedStatement getProductMetadataStmt;

	public OverDriveRecordGrouper(Connection dbConnection, String serverName, BaseIndexingLogEntry logEntry, Logger logger) {
		super(dbConnection, serverName, logEntry, logger);

		try {
			getOverDriveProductInfoStmt = dbConnection.prepareStatement("SELECT id, mediaType, title, subtitle, series, primaryCreatorName from overdrive_api_products WHERE overdriveId = ?");
			getProductMetadataStmt = dbConnection.prepareStatement("SELECT UNCOMPRESS(rawData) as rawData from overdrive_api_product_metadata where productId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);		} catch (SQLException e) {
			logEntry.incErrors("Unable to setup overdrive statements", e);
		}
	}

	Pattern wordsInParensPattern = Pattern.compile("\\(.*?\\)", Pattern.CASE_INSENSITIVE);
	public String processOverDriveRecord(String overdriveId) {
		try {
			getOverDriveProductInfoStmt.setString(1, overdriveId);
			ResultSet overDriveRecordRS = getOverDriveProductInfoStmt.executeQuery();
			if (overDriveRecordRS.next()) {
				long id = overDriveRecordRS.getLong("id");
				String mediaType = overDriveRecordRS.getString("mediaType");
				String title = overDriveRecordRS.getString("title");
				title = wordsInParensPattern.matcher(title).replaceAll("");
				String subtitle = overDriveRecordRS.getString("subtitle");
				String series = overDriveRecordRS.getString("series");
				String author = overDriveRecordRS.getString("primaryCreatorName");
				String primaryLanguage = "eng";
				getProductMetadataStmt.setLong(1, id);
				ResultSet metadataRS = getProductMetadataStmt.executeQuery();
				if (metadataRS.next()) {
					byte[] rawDataBytes = metadataRS.getBytes("rawData");
					if (rawDataBytes != null) {
						String metadata = new String(rawDataBytes, StandardCharsets.UTF_8);
						JSONObject productMetadata;
						try {
							productMetadata = new JSONObject(metadata);
							if (productMetadata.has("languages")) {
								JSONArray languagesFromMetadata = productMetadata.getJSONArray("languages");
								if (languagesFromMetadata.length() > 1) {
									primaryLanguage = "mul";
								} else {
									for (int i = 0; i < languagesFromMetadata.length(); i++) {
										JSONObject curLanguageObj = languagesFromMetadata.getJSONObject(i);
										String languageCode = curLanguageObj.getString("code");
										String threeLetterCode = translateValue("two_to_three_character_language_codes", languageCode.toLowerCase());
										if (threeLetterCode != null) {
											primaryLanguage = threeLetterCode;
										}
									}
								}
							}
							//Check to see if this should be a comic rather than an eBook
							if (productMetadata.has("subjects") && mediaType.equals("eBook")) {
								JSONArray subjectsFromMetadata = productMetadata.getJSONArray("subjects");
								for (int i = 0; i < subjectsFromMetadata.length(); i++) {
									JSONObject curSubjectObj = subjectsFromMetadata.getJSONObject(i);
									if (curSubjectObj.getString("value").equalsIgnoreCase("Comic and Graphic Books")) {
										mediaType = "eComic";
										break;
									}
								}
							}
						} catch (JSONException e) {
							logEntry.incErrors("Error loading raw data for OverDrive MetaData for record " + overdriveId, e);
						}
					}
				}
				metadataRS.close();
				overDriveRecordRS.close();
				return processOverDriveRecord(overdriveId, title, subtitle, series, author, mediaType, primaryLanguage);
			}
			overDriveRecordRS.close();
		} catch (SQLException e) {
			logEntry.incErrors("Error getting information about overdrive record for grouping", e);
		}
		return null;
	}

	private String processOverDriveRecord(String overdriveId, String title, String subtitle, String series, String author, String mediaType, String primaryLanguage) {
		RecordIdentifier primaryIdentifier = new RecordIdentifier("overdrive", overdriveId);
		//Remove invalid data from subtitles.
		if (subtitle != null && !subtitle.isEmpty()) {
			String subtitleLower = subtitle.toLowerCase(Locale.ROOT);
			//Overdrive typically makes the subtitle the series and volume which we don't want for grouping
			if (series != null && !series.isEmpty() && subtitleLower.contains(series.toLowerCase())){
				subtitle = "";
			//Remove book club notices and award winners
			}else if (subtitleLower.contains("book club") || subtitleLower.contains("award winner") || subtitleLower.contains("read with jenna")
					|| subtitleLower.contains("number one bestseller")) {
				subtitle = "";
			}
		}
		//Overdrive typically makes the subtitle the series and volume which we don't want for grouping
		if (title != null && series != null && title.toLowerCase().endsWith("--" + series.toLowerCase())) {
			title = title.substring(0, title.length() - (series.length() + 2));
		}

		return processRecord(primaryIdentifier, title, subtitle, author, mediaType, primaryLanguage, true);
	}
}
