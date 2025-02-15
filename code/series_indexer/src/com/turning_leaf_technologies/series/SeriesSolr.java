package com.turning_leaf_technologies.series;

import com.turning_leaf_technologies.dates.DateUtils;
import com.turning_leaf_technologies.indexing.Scope;
import com.turning_leaf_technologies.strings.AspenStringUtils;
import org.apache.solr.common.SolrInputDocument;

import java.util.Date;
import java.util.HashSet;

class SeriesSolr {
	private final SeriesIndexer seriesIndexer;
	private long id;
	private final HashSet<String> relatedRecordIds = new HashSet<>();
	private HashSet<String> authors = new HashSet<>();
	private String title;
	private final HashSet<String> contents = new HashSet<>(); //A list of the titles and authors for the list
	private String description;
	private long numTitles = 0;
	private long created;
//	private long owningLibrary;
//	private String owningLocation;
//	private boolean ownerCanShareListsInSearchResults = false;
	private long dateUpdated;

	SeriesSolr(SeriesIndexer seriesIndexer) {
		this.seriesIndexer = seriesIndexer;
	}

	SolrInputDocument getSolrDocument() {
		SolrInputDocument doc = new SolrInputDocument();
		doc.addField("id", id);
		doc.addField("recordtype", "series");

		doc.addField("alternate_ids", relatedRecordIds);

		doc.addField("title", title);
		doc.addField("title_display", title);

		doc.addField("title_sort", AspenStringUtils.makeValueSortable(title));

		doc.addField("author", authors);
		doc.addField("author_display", authors);

		doc.addField("table_of_contents", contents);
		doc.addField("description", description);
		doc.addField("keywords", description);

		doc.addField("num_titles", numTitles);

		Date dateAdded = new Date(created * 1000);
		doc.addField("days_since_added", DateUtils.getDaysSinceAddedForDate(dateAdded));

		Date dateUpdatedDate = new Date(dateUpdated * 1000);
		doc.addField("days_since_updated", DateUtils.getDaysSinceAddedForDate(dateUpdatedDate));

		int numValidScopes = 0;
		HashSet<String> relevantScopes = new HashSet<>();
		for (Scope scope: seriesIndexer.getScopes()) {
			boolean okToInclude = true;
//			if (scope.isLibraryScope()) {
//				okToInclude = (scope.getPublicListsToInclude() == 2) || //All public lists
//					((scope.getPublicListsToInclude() == 1) && (scope.getLibraryId() == owningLibrary)) || //All lists for the current library
//					((scope.getPublicListsToInclude() == 3) && ownerCanShareListsInSearchResults && (scope.getLibraryId() == owningLibrary || scope.getLibraryId() == -1 || owningLibrary == -1)) || //All lists for list publishers at the current library
//					((scope.getPublicListsToInclude() == 4) && ownerCanShareListsInSearchResults) //All lists for list publishers
//				;
//			} else {
//				okToInclude = (scope.getPublicListsToInclude() == 3) || //All public lists
//					((scope.getPublicListsToInclude() == 1) && (scope.getLibraryId() == owningLibrary)) || //All lists for the current library
//					((scope.getPublicListsToInclude() == 2) && scope.getScopeName().equals(owningLocation)) || //All lists for the current location
//					((scope.getPublicListsToInclude() == 4) && ownerCanShareListsInSearchResults && (scope.getLibraryId() == owningLibrary || scope.getLibraryId() == -1 || owningLibrary == -1)) || //All lists for list publishers at the current library
//					((scope.getPublicListsToInclude() == 5) && ownerCanShareListsInSearchResults && scope.getScopeName().equals(owningLocation)) || //All lists for list publishers the current location
//					((scope.getPublicListsToInclude() == 6) && ownerCanShareListsInSearchResults) //All lists for list publishers
//				;
//			}
			if (okToInclude) {
				numValidScopes++;
				doc.addField("local_time_since_added_" + scope.getScopeName(), DateUtils.getTimeSinceAddedForDate(dateAdded));
				doc.addField("local_days_since_added_" + scope.getScopeName(), DateUtils.getDaysSinceAddedForDate(dateAdded));

				doc.addField("local_time_since_updated_" + scope.getScopeName(), DateUtils.getTimeSinceAddedForDate(dateUpdatedDate));
				doc.addField("local_days_since_updated_" + scope.getScopeName(), DateUtils.getDaysSinceAddedForDate(dateUpdatedDate));
				relevantScopes.add(scope.getScopeName());
			}
		}

		if (numValidScopes == 0){
			return null;
		}else{
			doc.addField("scope_has_related_records", relevantScopes);
			return doc;
		}
	}

	void setTitle(String title) {
		this.title = title;
	}

	void setDescription(String description) {
		this.description = description;
	}

	void setAuthor(String author) {
		this.authors.add(author);
	}

	void addListTitle(String source, String groupedWorkId, Object title, Object author) {
		relatedRecordIds.add(source + ":" + groupedWorkId);
		contents.add(title + " - " + author);
		authors.add(author.toString());
		numTitles++;
	}

	void setCreated(long created) {
		this.created = created;
	}

	void setId(long id) {
		this.id = id;
	}

//	void setOwningLocation(String owningLocation) {
//		this.owningLocation = owningLocation;
//	}
//
//	void setOwningLibrary(long owningLibrary) {
//		this.owningLibrary = owningLibrary;
//	}
//
//	void setOwnerCanShareListsInSearchResults(boolean ownerCanShareListsInSearchResults){
//		this.ownerCanShareListsInSearchResults = ownerCanShareListsInSearchResults;
//	}

	long getNumTitles(){
		return numTitles;
	}

	public void setDateUpdated(long dateUpdated) {
		this.dateUpdated = dateUpdated;
	}

	public long getDateUpdated() {
		return dateUpdated;
	}
}
