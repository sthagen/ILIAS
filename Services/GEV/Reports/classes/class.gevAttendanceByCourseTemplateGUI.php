<?php
/* Copyright (c) 1998-2014 ILIAS open source, Extended GPL, see docs/LICENSE */#

/**
* Report "Attendance By Course-Template"
* for Generali
*
* @author	Nils Haagen <nhaagen@concepts-and-training.de>
* @version	$Id$
*
*
*/

require_once("Services/GEV/Reports/classes/class.catBasicReportGUI.php");
require_once("Services/GEV/Reports/classes/class.catFilter.php");
require_once("Services/CaTUIComponents/classes/class.catTitleGUI.php");
require_once("Services/GEV/Utils/classes/class.gevCourseUtils.php");
require_once("Modules/OrgUnit/classes/class.ilObjOrgUnit.php");
require_once("Services/GEV/Utils/classes/class.gevObjectUtils.php");
require_once("Services/GEV/Utils/classes/class.gevOrgUnitUtils.php");

class gevAttendanceByCourseTemplateGUI extends catBasicReportGUI{
	protected $orgu_membeships;
	protected $filtered_orgus;
	public function __construct() {
		
		parent::__construct();


		$this->title = catTitleGUI::create()
						->title("gev_rep_attendance_by_coursetemplate_title")
						->subTitle("gev_rep_attendance_by_coursetemplate_desc")
						->image("GEV_img/ico-head-edubio.png")
						;

	
		$this->table = catReportTable::create()
						->column("template_title", "title")
						->column("edu_program", "gev_edu_program")
						//->column("above1", "above1")
						
						->column("sum_booked_wbt", "sum_booked_WBT")
						->column("sum_attended_wbt", "sum_attended_WBT")
						
						->column("sum_booked", "sum_booked_nowbt")
						->column("sum_waiting", "sum_waiting")
						->column("sum_attended", "sum_attended_nowbt")
						->column("sum_excused", "sum_excused")
						->column("sum_unexcused", "sum_unexcused")
						->column("sum_exit", "sum_exit")
						
						->template("tpl.gev_attendance_by_coursetemplate_row.html", "Services/GEV/Reports")
						;

		$this->order = catReportOrder::create($this->table)
						//->mapping("date", "crs.begin_date")
						//->mapping("odbd", array("org_unit_above1", "org_unit_above2"))
						->defaultOrder("template_title", "ASC")
						;

		$this->table_sums = catReportTable::create()
						->column("sum_booked_wbt", "sum_booked_WBT")
						->column("sum_attended_wbt", "sum_attended_WBT")
						->column("sum_booked", "sum_booked_nowbt")
						->column("sum_waiting", "sum_waiting")
						->column("sum_attended", "sum_attended_nowbt")
						->column("sum_excused", "sum_excused")
						->column("sum_unexcused", "sum_unexcused")
						->column("sum_exit", "sum_exit")

						->template("tpl.gev_attendance_by_coursetemplate_sums_row.html", "Services/GEV/Reports")
						;
		$this->summed_data = array();
		$never_skip = $this->user_utils->getOrgUnitsWhereUserIsDirectSuperior();
		array_walk($never_skip, 
			function (&$obj_ref_id) {
				$aux = new ilObjOrgUnit($obj_ref_id["ref_id"]);
				$obj_ref_id = $aux->getTitle();
			}
		);
		$skip_org_units_in_filter_below = array('Nebenberufsagenturen');
		array_walk($skip_org_units_in_filter_below, 
			function(&$title) { 
				$title = ilObjOrgUnit::_getIdsForTitle($title)[0];
				$title = gevObjectUtils::getRefId($title);
				$title = gevOrgUnitUtils::getAllChildrenTitles(array($title));
			}
		);
		$skip_org_units_in_filter = array();
		foreach ($skip_org_units_in_filter_below as $org_units) {
			$skip_org_units_in_filter = array_merge($skip_org_units_in_filter, $org_units);
		}
		array_unique($skip_org_units_in_filter);
		$skip_org_units_in_filter = array_diff($skip_org_units_in_filter, $never_skip);
		$org_units_filter = array_diff( $this->user_utils->getOrgUnitNamesWhereUserIsSuperior(), $skip_org_units_in_filter);
		sort($org_units_filter);
		
		$this->filter = catFilter::create()
						->dateperiod( "period"
									, $this->lng->txt("gev_period")
									, $this->lng->txt("gev_until")
									, "usrcrs.begin_date"
									, "usrcrs.end_date"
									, date("Y")."-01-01"
									, date("Y")."-12-31"
									, false
									, " OR usrcrs.hist_historic IS NULL"
									)
						->multiselect( "org_unit"
									 , $this->lng->txt("gev_org_unit_short")
									 //, array("usr.org_unit", "org_unit_above1", "org_unit_above2")
									 , array("orgu.orgu_title")
									 , $org_units_filter
									 , array()
									 , ""
									 , 300
									 , 160	
									 )
						->multiselect("edu_program"
									 , $this->lng->txt("gev_edu_program")
									 , "edu_program"
									 , gevCourseUtils::getEduProgramsFromHisto()
									 , array()
									 , ""
									 , 200
									 , 160	
									 )
						->multiselect("type"
									 , $this->lng->txt("gev_course_type")
									 , "type"
									 , gevCourseUtils::getLearningTypesFromHisto()
									 , array()
									 , ""
									 , 200
									 , 160	
									 )
						->multiselect("template_title"
									 , $this->lng->txt("crs_title")
									 , "template_title"
									 , gevCourseUtils::getTemplateTitleFromHisto()
									 , array()
									 , ""
									 , 300
									 , 160	
									 )
						->multiselect("participation_status"
									 , $this->lng->txt("gev_participation_status")
									 , "participation_status"
									 , gevCourseUtils::getParticipationStatusFromHisto()
									 , array()
									 , ""
									 , 200
									 , 160
									 )
						->multiselect("booking_status"
									 , $this->lng->txt("gev_booking_status")
									 , "booking_status"
									 , catFilter::getDistinctValues('booking_status', 'hist_usercoursestatus')
									 , array()
									 , ""
									 , 200
									 , 160	
									 )
						->multiselect("venue"
									 , $this->lng->txt("gev_venue")
									 , "venue"
									 , catFilter::getDistinctValues('venue', 'hist_course')
									 , array()
									 , ""
									 , 300
									 , 160	
									 )
						->multiselect("provider"
									 , $this->lng->txt("gev_provider")
									 , "provider"
									 , catFilter::getDistinctValues('provider', 'hist_course')
									 , array()
									 , ""
									 , 300
									 , 160	
									 )
/*
						->multiselect("gender"
									 , $this->lng->txt("gender")
									 , "gender"
									 , array('f', 'm')
									 , array()
									 )
*/
						->static_condition(" usrcrs.hist_historic = 0")
						->action($this->ctrl->getLinkTarget($this, "view"))
						->compile()
						;
		$this->relevant_parameters = array(
			$this->filter->getGETName() => $this->filter->encodeSearchParamsForGET()
			); 
		$this->filtered_orgus = $this->filter->get("org_unit");
					

		$this->sql_sum_parts = array(

				"sum_booked" => "SUM(
						CASE 
							WHEN LCASE(usrcrs.booking_status) = 'gebucht'
							AND LCASE(usrcrs.participation_status) = 'nicht gesetzt'
							AND crs.type != 'Selbstlernkurs'
						THEN 1
						END 
					) AS sum_booked",

					"sum_booked_wbt" => "SUM(
						CASE 
							WHEN LCASE(usrcrs.booking_status) = 'gebucht'
							AND LCASE(usrcrs.participation_status) = 'nicht gesetzt'
							AND crs.type = 'Selbstlernkurs'
						THEN 1
						END 
					) AS sum_booked_wbt",


					"sum_waiting" => "SUM(
						CASE 
							WHEN usrcrs.booking_status = 'auf Warteliste'
							AND participation_status = 'nicht gesetzt'
						THEN 1
						END 
					) AS sum_waiting",

					"sum_attended" => "SUM(
						CASE 
							WHEN LCASE(usrcrs.participation_status) = 'teilgenommen'
							AND crs.type != 'Selbstlernkurs'
						THEN 1
						END 
					) AS sum_attended",

					"sum_attended_wbt" => "SUM(
						CASE 
							WHEN LCASE(usrcrs.participation_status) = 'teilgenommen'
							AND crs.type = 'Selbstlernkurs'
						THEN 1
						END 
					) AS sum_attended_wbt",


					"sum_excused" => "SUM(
						CASE 
							WHEN LCASE(usrcrs.participation_status) = 'fehlt entschuldigt'
						THEN 1
						END 
					) AS sum_excused",


					"sum_unexcused" => " SUM(
						CASE 
							WHEN LCASE(usrcrs.participation_status) = 'fehlt ohne Absage'
						THEN 1
						END 
					) AS sum_unexcused",

					"sum_exit" => "SUM(
						CASE 
							WHEN LCASE(usrcrs.participation_status) = 'canceled_exit'
						THEN 1
						END 
					) AS sum_exit"

			);
		$this->orgu_filter = 	
				"JOIN (SELECT DISTINCT usr_id ,".$this->db->quote($this->filtered_orgus[0])." AS orgu_title \n"
					."	FROM hist_userorgu \n"
					." 	WHERE ".$this->db->in("orgu_title", $this->filtered_orgus, false, "text")." \n"
					."	AND hist_historic = 0 AND `action` >= 0) as orgu ON usrcrs.usr_id = orgu.usr_id \n";
		$this->query = catReportQuery::create()
						//->distinct()

						->select("crs.template_title")
						->select("crs.edu_program")

						->select_raw($this->sql_sum_parts['sum_booked_wbt'])
						->select_raw($this->sql_sum_parts['sum_attended_wbt'])

						->select_raw($this->sql_sum_parts['sum_booked'])
						->select_raw($this->sql_sum_parts['sum_attended'])
						->select_raw($this->sql_sum_parts['sum_waiting'])
						->select_raw($this->sql_sum_parts['sum_excused'])
						->select_raw($this->sql_sum_parts['sum_unexcused'])
						->select_raw($this->sql_sum_parts['sum_exit'])
						->from("hist_usercoursestatus usrcrs")
						->join("hist_course crs")
							->on("crs.crs_id = usrcrs.crs_id AND crs.hist_historic = 0");
		if(count($this->filtered_orgus)>0) {
			$this->query->raw_join($this->orgu_filter );
		}
		$this->query 	->group_by("crs.template_title")
						->compile();

	

	}

	protected function transformResultRow($rec) {
		//foreach(array_keys($this->table_sums->columns) as $field) {
		//	if (! array_key_exists($field, $this->summed_data)) {
		//		$this->summed_data[$field] = 0;
		//	}
			
		//	$this->summed_data[$field] +=  intval($rec[$field]);
		//}
			
		return $this->replaceEmpty($rec);
	}

	protected function renderView() {

		$main_table = $this->renderTable();
		return 	$this->renderSumTable()
				.$main_table;
	}


	private function renderSumTable(){
		$table = new catTableGUI($this, "view");
		$table->setEnableTitle(false);
		$table->setTopCommands(false);
		$table->setEnableHeader(true);
		$table->setRowTemplate(
			$this->table_sums->row_template_filename, 
			$this->table_sums->row_template_module
		);

		$table->addColumn("", "blank", "0px", false);
		foreach ($this->table_sums->columns as $col) {
			$table->addColumn( $col[2] ? $col[1] : $this->lng->txt($col[1])
							 , $col[0]
							 , $col[3]
							 );
		}		
		$sum_sql = 
		"SELECT "
		."SUM( CASE WHEN LCASE(booking_status) = 'gebucht' AND LCASE(participation_status) = 'nicht gesetzt' AND type = 'Selbstlernkurs' THEN 1 END ) AS sum_booked_wbt,\n"
		."SUM( CASE WHEN LCASE(participation_status) = 'teilgenommen' AND type = 'Selbstlernkurs' THEN 1 END ) AS sum_attended_wbt,\n"
		."SUM( CASE WHEN LCASE(booking_status) = 'gebucht' AND LCASE(participation_status) = 'nicht gesetzt' AND type != 'Selbstlernkurs' THEN 1 END ) AS sum_booked,\n"
		."SUM( CASE WHEN LCASE(participation_status) = 'teilgenommen' AND type != 'Selbstlernkurs' THEN 1 END ) AS sum_attended,\n"
		."SUM( CASE WHEN booking_status = 'auf Warteliste' AND participation_status = 'nicht gesetzt' THEN 1 END ) AS sum_waiting,\n"
		."SUM( CASE WHEN LCASE(participation_status) = 'fehlt entschuldigt' THEN 1 END ) AS sum_excused,\n"
		."SUM( CASE WHEN LCASE(participation_status) = 'fehlt ohne Absage' THEN 1 END ) AS sum_unexcused,\n"
		."SUM( CASE WHEN LCASE(participation_status) = 'canceled_exit' THEN 1 END ) AS sum_exit \n"
		."FROM( \n"
		."	SELECT DISTINCT usr.user_id, crs.crs_id, usrcrs.booking_status, \n"
		."		usrcrs.participation_status, crs.type \n"
		."		FROM `hist_user` usr \n" 
		."			LEFT JOIN `hist_usercoursestatus` usrcrs ON usrcrs.usr_id = usr.user_id AND usr.hist_historic = 0 \n"
		."			LEFT JOIN `hist_course` crs ON usrcrs.crs_id = crs.crs_id AND crs.hist_historic = 0 \n"
		."			LEFT JOIN hist_userorgu orgu ON orgu.usr_id = usr.user_id \n"
		.$this->queryWhere()
		.") as temp";
		$res = $this->db->query($sum_sql);
		$this->summed_data = $this->db->fetchAssoc($res);
		$cnt = 1;
		$table->setLimit($cnt);
		$table->setMaxCount($cnt);

		if(count($this->summed_data) == 0) {
			foreach(array_keys($this->table_sums->columns) as $field) {
				$this->summed_data[$field] = 0;
			}
		}

		$table->setData(array($this->summed_data));
		return $table->getHtml();
	}

}