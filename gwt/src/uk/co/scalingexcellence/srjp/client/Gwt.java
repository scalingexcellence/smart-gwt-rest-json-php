package uk.co.scalingexcellence.srjp.client;

import com.google.gwt.core.client.EntryPoint;
import com.google.gwt.user.client.Window;
import com.smartgwt.client.data.AdvancedCriteria;
import com.smartgwt.client.data.DataSource;
import com.smartgwt.client.data.RestDataSource;
import com.smartgwt.client.data.fields.DataSourceBooleanField;
import com.smartgwt.client.data.fields.DataSourceDateField;
import com.smartgwt.client.data.fields.DataSourceEnumField;
import com.smartgwt.client.data.fields.DataSourceFloatField;
import com.smartgwt.client.data.fields.DataSourceTextField;
import com.smartgwt.client.types.Alignment;
import com.smartgwt.client.types.DSDataFormat;
import com.smartgwt.client.types.DSProtocol;
import com.smartgwt.client.types.ListGridEditEvent;
import com.smartgwt.client.types.OperatorId;
import com.smartgwt.client.types.RowEndEditAction;
import com.smartgwt.client.widgets.Button;
import com.smartgwt.client.widgets.Canvas;
import com.smartgwt.client.widgets.IButton;
import com.smartgwt.client.widgets.events.ClickEvent;
import com.smartgwt.client.widgets.events.ClickHandler;
import com.smartgwt.client.widgets.form.FilterBuilder;
import com.smartgwt.client.widgets.form.validator.FloatPrecisionValidator;
import com.smartgwt.client.widgets.form.validator.FloatRangeValidator;
import com.smartgwt.client.widgets.grid.ListGrid;
import com.smartgwt.client.widgets.grid.ListGridRecord;
import com.smartgwt.client.widgets.layout.HLayout;
import com.smartgwt.client.widgets.layout.VLayout;
import com.smartgwt.client.widgets.layout.VStack;

public class Gwt implements EntryPoint {
	
	private class MHLayout extends HLayout {
    	public void addButton(String label, ClickHandler ch) {
            Button db = new Button(label);
            db.addClickHandler(ch);
            this.addMember(db);
    	}
	}
	
	private HLayout createMenu(final ListGrid table) {
		MHLayout options = new MHLayout();        
        options.setWidth100();
        options.setHeight(26);
        
        options.addButton("New", new ClickHandler() {
            public void onClick(ClickEvent event) {
                table.startEditingNew();
            }
        });
        
        options.addButton("Delete", new ClickHandler() {
            public void onClick(ClickEvent event) {
                if (Window.confirm("Are you sure you want to permantly delete those entries now?")) {
                    table.removeSelectedData();
                }
            }
        });
        
        options.addButton("Save", new ClickHandler() {
            public void onClick(ClickEvent event) {
                table.saveAllEdits();
            }
        });

        options.addButton("Undo", new ClickHandler() {
            public void onClick(ClickEvent event) {
                if (Window.confirm("Are you sure you want to discard your edits now?")) {
                    table.discardAllEdits();
                }
            }
        });
        
        options.addButton("Refresh", new ClickHandler() {
            public void onClick(ClickEvent event) {
            	table.invalidateCache();
            }
        });
        
        return options;
	}
	
	
	public void onModuleLoad() {

		// Nested table
		final RestDataSource dataSource = new RestDataSource();
		dataSource.setID("ItemsDs");
		dataSource.setDataFormat(DSDataFormat.JSON);

		DataSourceTextField itemNameField = new DataSourceTextField("itemName", "Item", 128, true);
		DataSourceTextField pkField = new DataSourceTextField("SKU", "SKU", 10, true);
		pkField.setPrimaryKey(true);

		DataSourceTextField descriptionField = new DataSourceTextField( "description", "Description", 2000);
		DataSourceTextField categoryField = new DataSourceTextField("category", "Category", 128, true);
		categoryField.setForeignKey("supplyCategoryDS.categoryName");

		DataSourceEnumField unitsField = new DataSourceEnumField("units", "Units", 5);
		unitsField.setValueMap("Roll", "Ea", "Pkt", "Set", "Tube", "Pad", "Ream", "Tin", "Bag", "Ctn", "Box");

		DataSourceFloatField unitCostField = new DataSourceFloatField("unitCost", "Unit Cost", 5);
		FloatRangeValidator rangeValidator = new FloatRangeValidator();
		rangeValidator.setMin(0);
		rangeValidator.setErrorMessage("Please enter a valid (positive) cost");

		FloatPrecisionValidator precisionValidator = new FloatPrecisionValidator();
		precisionValidator.setPrecision(2);
		precisionValidator.setErrorMessage("The maximum allowed precision is 2");

		unitCostField.setValidators(rangeValidator, precisionValidator);

		dataSource.setFields(pkField, itemNameField, descriptionField, categoryField, unitsField, unitCostField);
		dataSource.setDataURL("api/");

		// Main table
		final RestDataSource supply = new RestDataSource();
		supply.setID("supplyCategoryDS");
		supply.setDataFormat(DSDataFormat.JSON);

		DataSourceTextField itemNameField2 = new DataSourceTextField("categoryName", "Item", 128, true);
		itemNameField2.setPrimaryKey(true);
		
		DataSourceTextField parentField = new DataSourceTextField("parentID", null);
		parentField.setHidden(true);
		parentField.setRequired(true);
		parentField.setRootValue("root");
		parentField.setForeignKey("supplyCategoryDS.categoryName");

		supply.setFields(itemNameField2, parentField);
		supply.setDataURL("api/");

		//Enable POST data
		//dataSource.setDataProtocol(DSProtocol.POSTPARAMS);
		//supply.setDataProtocol(DSProtocol.POSTPARAMS);
		

		//final TreeGrid listGrid = new TreeGrid() {
		final ListGrid listGrid = new ListGrid() {
			public DataSource getRelatedDataSource(ListGridRecord record) {
				return dataSource;
			}

			@Override
			protected Canvas getExpansionComponent(final ListGridRecord record) {

				final ListGrid grid = this;

				VLayout layout = new VLayout(5);
				layout.setPadding(5);

				final ListGrid countryGrid = new ListGrid();
				countryGrid.setWidth(500);
				countryGrid.setHeight(224);
				countryGrid.setCellHeight(22);
				countryGrid.setDataSource(getRelatedDataSource(record));
				countryGrid.fetchRelatedData(record, supply);

				countryGrid.setCanEdit(true);
				countryGrid.setModalEditing(true);
				countryGrid.setEditEvent(ListGridEditEvent.DOUBLECLICK);
				countryGrid.setListEndEditAction(RowEndEditAction.NEXT);
				//countryGrid.setAutoSaveEdits(false);

				layout.addMember(countryGrid);

				HLayout hLayout = new HLayout(10);
				hLayout.setAlign(Alignment.CENTER);

				IButton saveButton = new IButton("Save");
				saveButton.setTop(250);
				saveButton.addClickHandler(new ClickHandler() {
					public void onClick(ClickEvent event) {
						countryGrid.saveAllEdits();
					}
				});
				hLayout.addMember(saveButton);

				IButton discardButton = new IButton("Discard");
				discardButton.addClickHandler(new ClickHandler() {
					public void onClick(ClickEvent event) {
						countryGrid.discardAllEdits();
					}
				});
				hLayout.addMember(discardButton);

				IButton closeButton = new IButton("Close");
				closeButton.addClickHandler(new ClickHandler() {
					public void onClick(ClickEvent event) {
						grid.collapseRecord(record);
					}
				});
				hLayout.addMember(closeButton);

				layout.addMember(hLayout);

				return layout;
			}
		};

		listGrid.setWidth(600);
		listGrid.setHeight(500);
		//listGrid.setDrawAheadRatio(4);
		listGrid.setCanExpandRecords(true);

		listGrid.setDataSource(supply);
		listGrid.setShowFilterEditor(true);  
		//listGrid.setAllowFilterExpressions(true); 
		listGrid.setFilterOnKeypress(true);  
		//listGrid.setFetchDelay(500);
		listGrid.setShowAllRecords(false);
		listGrid.setAutoFetchData(true);
		listGrid.setDataPageSize(5);
		listGrid.setDrawAllMaxCells(5);
		
		listGrid.setCanEdit(true);
		listGrid.setModalEditing(true);
		listGrid.setEditEvent(ListGridEditEvent.DOUBLECLICK);
		listGrid.setListEndEditAction(RowEndEditAction.NEXT);
		listGrid.setAutoSaveEdits(false);
		

		//final FilterBuilder filterBuilder = new FilterBuilder();
		//filterBuilder.setDataSource(supply);
		//filterBuilder.setTopOperatorAppearance(TopOperatorAppearance.RADIO);
		
		final FilterBuilder filterBuilder = new FilterBuilder();  
        filterBuilder.setDataSource(supply);  
        AdvancedCriteria criteria = new AdvancedCriteria(OperatorId.AND, new AdvancedCriteria[] {  
                new AdvancedCriteria("categoryName", OperatorId.ISTARTS_WITH, "C"),  
                new AdvancedCriteria(OperatorId.OR, new AdvancedCriteria[] {  
                    new AdvancedCriteria("volume", OperatorId.LESS_THAN, "584"),  
                    new AdvancedCriteria("volume", OperatorId.LESS_OR_EQUAL, "43"),
                    new AdvancedCriteria("volume", OperatorId.GREATER_THAN_FIELD, "volume")
                })  
        });  
        filterBuilder.setCriteria(criteria);

		IButton filterButton = new IButton("Filter");
		filterButton.addClickHandler(new ClickHandler() {
			public void onClick(ClickEvent event) {
				listGrid.filterData(filterBuilder.getCriteria());
			}
		});

		VStack vStack = new VStack(10);
		vStack.addMember(filterBuilder);
		vStack.addMember(filterButton);
		vStack.addMember(createMenu(listGrid));
		vStack.addMember(listGrid);

		vStack.draw();
	}
}

