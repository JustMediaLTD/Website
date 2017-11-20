static char *rcsid="$Id: .exrc,v 1.2 2003/11/13 13:38:47 you Exp you $ --- Ohad Aloni";
/************************************************************/
#include	"you.h"
#include	"gld.h"
/************************************************************/
#define	GLD_MAX_ITEMS (16*1024)
#define	 MAX_TOF_RPRT 40
#define	gldIsZero(Z) ( (Z) < 0.004 && (Z) > -0.004 )
/************************************************************/
static lastAddDate = 0;
/******************************/
static char *gldFname[3] = { "category", "toFrom", "Balance" };
static char *repFdesc[3] = { "Category", "Paid To/From", "Balances" };
static char *repFvalClass[3] = {
	"gldCategoryValClass",
	"gldToFromValClass",
	"gldBalancesValClass"
	};
static char *repRestrClass[3] = {
	"gldCategoryRestrictClass",
	"gldToFromRestrictClass",
	"gldBalancesRestrictClass"
	};
static char *repPosClass[3] = {
	"gldCategoryTotalClass",
	"gldToFromTotalClass",
	"gldBalancesTotalClass"
	};
static char *repNegClass[3] = {
	"gldCategoryNegTotalClass",
	"gldToFromNegTotalClass",
	"gldBalancesNegTotalClass"
	};
static char *repAlt[3] = {
	"Show Detail of this Category",
	"Show Detail of this Payee/Payer",
	"Show Statement Ending with this date"
	};
static char *repfvalues[3][1024];
static int stCloseDates[1024]; /* ints of repfvalues[2] */
static int repnvalues[3] = { -2, -2, -2 };
/************************************************************/

static
gldStPrevDate(uid, fid, closeDate)
int uid, fid, closeDate;
{
	char cmd[1024];
	int ret;

	sprintf(cmd, "%s where uid = %d and fid = %d and date < %d %s",
		"select max(date) from gld",
		uid, fid, closeDate,
		"and closed > 0"
		);
	ret = engdb_getint(cmd);
	if ( ret < 0 )
		return(0);;

	return(ret);
}

/******************************/


static
gld_prevDate(uid, fid, isStatement, closeDate)
int uid, fid;
int isStatement, closeDate;
{
	char cmd[1024];
	static int thePrevDate = -1 ;

	if ( thePrevDate != -1 )
		return(thePrevDate);

	if ( isStatement ) {
		thePrevDate = gldStPrevDate(uid, fid, closeDate);
		return(thePrevDate);;
	}

	sprintf(cmd, "%s where uid = %d and fid = %d and closed > 0",
		"select max(date) from gld",
		uid, fid);
	thePrevDate = engdb_getint(cmd);
	if ( thePrevDate < 0 )
		thePrevDate = 0;

	return(thePrevDate);
}

/************************************************************/

/*
 * it is not an error to change or add consolidated data 
 * this can occur when entering or changing the date
 * or when accessing data from the restricted (unconsolidated) view
 * but the results do not show on screen, except for the previous balance
 * which may have changed
 */

static
gldWarnPreCons(date, uid, fid)
int date, uid, fid;
{
	char msg[1024], sdate[1024], pdate[1024];
	int p;

	p = gld_prevDate(uid, fid, 0, 0);

	if ( date > p )
		return;

	eng_intwdate_fmt(pdate, &p);
	eng_intwdate_fmt(sdate, &date);

	sprintf(msg, "Note: %s is before %s", sdate, pdate);
	YOU_MSG(msg);
}

/************************************************************/

static double
gld_prevBalance(uid, fid, isStatement, stCloseDate)
int uid, fid, isStatement, stCloseDate;
{
	char cmd[1024];

	sprintf(cmd, "%s where uid = %d and fid = %d and date <= %d",
		"select sum(-amount) from gld",
		uid, fid,
		gld_prevDate(uid, fid, isStatement, stCloseDate)
		);
	return(engdb_getdouble(cmd));
}

/************************************************************/

static
gldSelect(which, Uid, fid)
int which;
int Uid, fid;
{
	int i;
	char s[1024];

	sprintf(s, "gldSEL%s", gldFname[which]);
	setenv("DIALOGID", s);
	sprintf(s, "gldSEL%sClass", gldFname[which]);
	setenv("CLASSNAME", s);

	youDialogFrame(1);

	gldRepData(Uid, fid);

	if ( repnvalues[which] <= 0 || repnvalues[which] == 1 && ! *repfvalues[which][0] ) {
		youDialogFrame(0);
		return(0);
	}

	for(i=0;i<repnvalues[which];i++)
		if ( *repfvalues[which][i] )
			printf("\t\t\t<A HREF=\"javascript:gldSet(%d, '%s')\">%s</A>%s\n",
				which,
				you_jsStr(repfvalues[which][i]),
				repfvalues[which][i],
				(i == repnvalues[which] - 1 ) ? "" : "<BR>"
				);

	youDialogFrame(0);
	return(1);
}

/************************************************************/

static
gldGetEnv(gld)
GLD *gld;
{
	char searchS[1024];
	char searchOp[1024];
	int isSearch = 0;


	if ( (gld->date = youGetEnvDate("date")) == -1 ) {
		YOU_MSG2(getenv("date"), "Invalid date format");
		return(0);
	}

	youGetEnvStr(gld->category, "category");
	youGetEnvStr(searchS, "amount");
	if ( sin_intl0(*searchS, '>', '<', '=', 0) ) {
		isSearch = 1;
		setenv("restrFname", "amount");
		sprintf(searchOp, "%c", *searchS);
		setenv("restrVal", searchS+1);
		setenv("searchOp", searchOp);
		setenv("searchLeftQuote", "");
		setenv("searchRightQuote", "");
	} else
		youGetEnvDouble(&gld->amount, "amount");
	youGetEnvStr(gld->toFrom, "toFrom");
	youGetEnvStr(gld->description, "description");
	if ( *gld->description == '=' ) {
		isSearch = 1;
		setenv("restrFname", "description");
		setenv("restrVal", gld->description+1);
		setenv("searchOp", "like");
		setenv("searchLeftQuote", "'%");
		setenv("searchRightQuote", "%'");
	}

	youGetEnvStr(gld->ref, "ref");
	if ( *gld->ref == '=' ) {
		isSearch = 1;
		setenv("restrFname", "ref");
		setenv("restrVal", gld->ref+1);
		setenv("searchOp", "like");
		setenv("searchLeftQuote", "'%");
		setenv("searchRightQuote", "%'");

	}
	youGetEnvInt(&gld->closed, "closed");
	youGetEnvInt(&gld->entered, "entered");


	if ( 		! isSearch &&
				! *gld->category &&
				! *gld->toFrom &&
				! *gld->description &&
				! *gld->ref &&
				gldIsZero(gld->amount)
			) {
		YOU_MSG("empty entry ignored");
		return(0);
	}

	if ( ! gld->entered )
		gld->entered = time(0);
	if ( gld->date == 0 )
		gld->date = day_today();

	return(1);
}

/******************************/

gld_add()
{
	GLD gldbuf, *gld = &gldbuf;
	int isSearch;

	bzero(gld, sizeof(GLD));

	youGetEnvUFE(&gld->uid, &gld->fid, (int *)0);

	if ( gld->fid == 0 ) {
		YOU_ERROR("lost Fid");
		YOU_MSG("Communication Error");
		return(0);
	}

	if ( ! gldGetEnv(gld) )
		return(gld_main(getenv("File"), gld->uid, gld->fid));

	/* searchOp, if at all, set by gldGetEnv */
	isSearch = (getenv("searchOp") != 0 );

	if ( isSearch )
		return(gld_main(getenv("File"), gld->uid, gld->fid));

	gld->closed = 0 ;

	if ( ! eng_insert(gld, "gld") )
		YOU_ERROR0();
	else
		gldWarnPreCons(gld->date, gld->uid, gld->fid);
	lastAddDate = gld->date;
	gld_main(getenv("File"), gld->uid, gld->fid);

	return(1);
}

/******************************/

gld_update()
{
	GLD gld;
	char *p;

	if ( (p=getenv("gldAdd")) != 0 && strcmp(p, "Copy") == 0 )
		return(gld_add());

	bzero(&gld, sizeof(GLD));
	youGetEnvUFE(&gld.uid, &gld.fid, &gld.eng_id);

	if ( gld.fid == 0 ) {
		YOU_ERROR("lost Fid");
		YOU_MSG("Communication Error");
		return(0);
	}

	if ( ! you_top4isSecure("gld", gld.uid, gld.eng_id) )
		return(0);

	if ( ! gldGetEnv(&gld) )
		return(gld_main(getenv("File"), gld.uid, gld.fid));

	if ( ! eng_update(&gld, "gld") )
		YOU_ERROR0();
	else
		gldWarnPreCons(gld.date, gld.uid, gld.fid);

	gld_main(getenv("File"), gld.uid, gld.fid);
	return(1);
}

/************************************************************/

/* this is showChange for device and Opera */

gld_change()
{
	int uid, eid, fid;
	char sdate[1024], samount[1024], w[1024];
	GLD *gld;
	char *File;

	youGetEnvUFE(&uid, &fid, &eid);

	if ( fid == 0 ) {
		YOU_ERROR("lost Fid");
		YOU_MSG("Communication Error");
		return(0);
	}

	File = getenv("File");
	sprintf(w, "where uid = %d and fid = %d and eng_id = %d",
		uid, fid, eid);
	if ( engdb_getobjects_by_where(&gld, 1, "gld", w) != 1 ) {
		YOU_LOG("Amateur");
		return(0);
	}

	if ( gld->date != 0 )
		eng_intwdate_fmt(sdate, &gld->date);
	else
		*sdate = 0;
	setenv("date", sdate);

	setenv("category", gld->category);
	setenv("toFrom", gld->toFrom);
	setenv("description", gld->description);
	setenv("ref", gld->ref);
	you_setenv_int("closed", gld->closed);
	you_setenv_int("entered", gld->entered);

	sprintf(samount, "%.2lf", gld->amount);
	setenv("amount", samount);

	you_setenv_int("Eid", eid);
	setenv("eFile", you_escape(File));

	gld_title(File, (char *)0);

	if ( you_isDevice() )
		you_include("gld/devChange.h");
	else if ( you_isOpera() )
		you_include("gld/operaChange.h");
	else 
		YOU_MSG2(you_agent(), "Unexpected Browser");

	return(1);
}

/************************************************************/

gld_cons()
{
	int uid, fid, eid;
	int ceid;
	char cmd[1024];
	char *File;
	int date;

	youGetEnvUFE(&uid, &fid, &eid);

	if ( fid == 0 ) {
		YOU_ERROR("lost Fid");
		YOU_MSG("Communication Error");
		return(0);
	}

	sprintf(cmd, "select uid from gld where eng_id = %d", eid);
	if ( engdb_getint(cmd) != uid ) {
		YOU_LOG("Amateur");
		return(0);
	}

	File = getenv("File");

	sprintf(cmd, "select date from gld where eng_id = %d", eid);
	date = engdb_getint(cmd);
	if ( date < 0 ) {
		YOU_ERROR("No date for consolidation");
		gld_main(File, uid, fid);
		return(0);
	}
	sprintf(cmd,
		"select max(eng_id) from gld where uid = %d and fid = %d and date = %d",
		uid, fid, date);
	
	if ( (ceid = engdb_getint(cmd)) <= 0 ) {
		YOU_ERROR("Consolidation date error");
		gld_main(File, uid, fid);
		return(0);
	}
	sprintf(cmd, "update gld set closed = %d, wasClosed = 1 where eng_id = %d",
		day_today(), 
		ceid
		);
	engdb_sql(cmd);
	gld_main(File, uid, fid);
	return(1);
}

/************************************************************/

gld_uncons()
{
	int uid, fid, dt;
	char cmd[1024];

	youGetEnvUFE(&uid, &fid, (int *)0);

	if ( fid == 0 ) {
		YOU_ERROR("lost Fid");
		YOU_MSG("Communication Error");
		return(0);
	}
	sprintf(cmd, "%s where uid = %d and fid = %d and closed != 0",
		"select max(date) from gld",
		uid, fid);

	if ( (dt = engdb_getint(cmd)) < 0 ) {
		YOU_LOG("Amateur");
		return(0);
	}

	sprintf(cmd, "%s where uid = %d and fid = %d and date = %d",
		"update gld set closed = 0",
		uid, fid, dt);
	engdb_sql(cmd);
	gld_main(getenv("File"), uid, fid);
	return(1);
}

/************************************************************/

static
gld_prev(prevDate, prevBal)
int prevDate;
double prevBal;
{
	char sdate[1024], sbal[1024];

	eng_intwdate_fmt(sdate, &prevDate);
	setenv("prevDate", sdate);

	setenv("prevBalClass", ( prevBal < -0.004 ) ? "gldNegPrev" : "gldPosPrev");
	if ( prevBal < 0 && prevBal >= -0.004 )
		strcpy(sbal, "0.00");
	else
		sprintf(sbal, "%.2lf", prevBal);

	setenv("prevBal", sbal);
	setenv("eFile", you_escape(getenv("File")));
	you_include(you_isDevice() ? "gld/devPrev.h" : "gld/prev.h");
}

/************************************************************/

static
gldMonthlyLine(date, bal)
double bal;
int date;
{
	char sdate[1024], sbal[1024];
	int endDate;

	setenv("gldLineClass", "gldMonthLine");

	/*
	 * the date on the report is the first day of the month
	 * as is convenienced by the stored proc,
	 * I am converting it to the last day of the month
	 */
	endDate = yd_dsub(yd_madd(date));

	eng_intwdate_fmt(sdate, &endDate);
	setenv("sEndDate", sdate);

	setenv("negAmountClass", "class=gldNegAmount");

	sprintf(sbal, "%.2lf", -bal);
	setenv("amount", sbal); /* !!! */

	you_include("gld/monthlyTotals.h");
}

/************************************************************/

static
gld_line(bal, gld, isodd, nextIsNextMonth)
double *bal;
GLD *gld;
int isodd;
int nextIsNextMonth;
{
	char *devSdate, sdate[1024], samount[1024], sbal[1024];
	int isdev;
	int isMonthly;

	isdev = you_isDevice();
	isMonthly = getenv("gldMonthly") != 0;

	setenv("gldLineClass", isodd ? "gldOddLine" : "gldEvenLine");

	setenv("category", gld->category);

	if ( isdev )
		devSdate = you_stdDateFmt(gld->date);
	else
		eng_intwdate_fmt(sdate, &gld->date);
	setenv("date", isdev ? devSdate : sdate);

	if ( gld->amount < -0.004 )
		setenv("negAmountClass", "class=gldNegAmount");
	else
		setenv("negAmountClass", "");
	sprintf(samount, "%.2lf", gld->amount);
	setenv("amount", samount);

	setenv("toFrom", gld->toFrom);

	setenv("description", gld->description);

	setenv("ref", gld->ref);

	*bal += -gld->amount;

	if ( *bal < -0.004 )
		setenv("negBalClass", "class=gldNegBal");
	else
		setenv("negBalClass", "");
	if ( *bal < 0.0 && *bal > -0.004 )
		*bal = 0.0 ;
	sprintf(sbal, "%.2lf", *bal);
	setenv("balance", sbal);

	you_setenv_int("lEid", gld->eng_id);

	setenv("eFile", you_escape(getenv("File")));
	if ( gld->wasClosed )
		setenv("CONSGIF", "redCons.gif");
	else if ( nextIsNextMonth )
		setenv("CONSGIF", "whiteCons.gif");
	else
		setenv("CONSGIF", "cons.gif");

	you_include(isMonthly ? "gld/monthlyLine.h" : ( isdev ? "gld/devLine.h" : "gld/line.h"));
}

/************************************************************/

static
gld_send_data(gld, n)
GLD *gld[];
int n;
{
	char js[1024*64];
	char s[1024*4];
	int i;

	strcpy(js, "\ng = new Array();\n");
	for(i=0;i<n;i++) {
		sprintf(s,
		"%s = new %s('%s', %d, %.2lf, '%s', '%s', '%s', %d, %d, %d);\n",
			"g[g.length]",
			"youGldItem",
			you_jsStr(gld[i]->category),
			gld[i]->date,
			gld[i]->amount,
			you_jsStr(gld[i]->toFrom),
			you_jsStr(gld[i]->description),
			you_jsStr(gld[i]->ref),
			gld[i]->closed,
			gld[i]->entered,
			gld[i]->eng_id
			);
		strcat(js, s);
	}
	strcat(js, "\nyou.gld.data = g;\n");
	you_js(js);
}

/************************************************************/

static
gldReportLine(uid, fid, n, which)
int uid, fid;
int n;
int which;
{
	char cmd[1024];
	double tot;
	char stot[1024];
	char *totClass;
	char *fval;

	fval = repfvalues[which][n];

	if ( which == 2 )
		sprintf(cmd, "%s where uid = %d and fid = %d and date <= %d",
			"select sum(-amount) from gld",
			uid, fid, stCloseDates[n]);
	else
		sprintf(cmd, "%s where uid = %d and fid = %d and %s = '%s'",
			"select sum(amount) from gld",
			uid, fid, gldFname[which], youdb_strFmt(fval));
	
	tot = engdb_getdouble(cmd);
	sprintf(stot, "%.2lf", ( tot < 0.0 && tot > -0.004) ? 0.0 : tot);
	setenv("Total", stot);

	you_setenv_int("which", which);
	setenv("fval", fval);

	setenv("restrictClass", repRestrClass[which]);
	if ( tot < -0.004 )
		totClass = repNegClass[which];
	else
		totClass = repPosClass[which];
	setenv("totalClass", totClass);

	setenv("repAlt", repAlt[which]);

	setenv("fvalClass", repFvalClass[which]);

	if ( which == 2 ) {
		you_setenv_int("cdate", stCloseDates[n]);
		you_include("gld/balLine.h");
	}
	else
		you_include("gld/repLine.h");
}

/******************************/

static
gldReportBy(uid, fid, which)
int uid, fid;
int which;
{
	int i;

	if ( repnvalues[which] <= 0 )
		return(0);
	if ( which == 1 && repnvalues[1] > MAX_TOF_RPRT )
		return(0);
	setenv("reportBy", gldFname[which]);
	setenv("reportByDesc", repFdesc[which]);
	you_include("gld/reportTableTag.h");
	for(i=0;i<repnvalues[which];i++)
		gldReportLine(uid, fid, i, which);
	printf("</TABLE>\n");
	return(1);
}

/******************************/

static
gldRepData(uid, fid)
int uid, fid;
{
	int i;
	char cmd[1024];
	char sdate[1024];

	if ( repnvalues[0] != -2 )
		return(1);

	for(i=0;i<2;i++) {
		sprintf(cmd, "%s %s from gld where uid = %d and fid = %d %s %s",
			"select distinct",
			gldFname[i], uid, fid,
			"order by", gldFname[i]);
		repnvalues[i] = engdb_getstrings(repfvalues[i], 1024, cmd);
	}

	sprintf(cmd, "%s where uid = %d and fid = %d and closed > 0",
		"select date from gld", uid, fid);
	repnvalues[2] =
		engdb_getitems_by_cmd(stCloseDates, 1024, cmd, "ENG_int", 0);
	for(i=0;i<repnvalues[2];i++)
		repfvalues[2][i] =
			strdup(eng_intwdate_fmt(sdate, &stCloseDates[i]));
	return(1);
}

/******************************/

static
gld_reports(uid, fid)
int uid, fid;
{
	if ( repnvalues[0] <= 0 && (repnvalues[1] <= 0 || repnvalues[1] > MAX_TOF_RPRT) )
		return(0);

	you_include("gld/reportsTableTag.h");
	printf("<TR><TD VALIGN=\"TOP\">\n");
	gldReportBy(uid, fid, 0);
	printf("</TD><TD VALIGN=\"TOP\">\n");
	gldReportBy(uid, fid, 1);
	printf("</TD><TD VALIGN=\"TOP\">\n");
	gldReportBy(uid, fid, 2);
	printf("</TD></TR></TABLE>\n");
	return(1);
}

/************************************************************/

static
gld_table(uid, fid, prevDate, prevBal, gld, n)
int uid, fid;
int prevDate;
double prevBal;
GLD *gld[];
int n;
{
	static double curBal;
	int i;
	int isie = you_isMSIE();
	int isdev = you_isDevice();
	int isns = you_isNS();
	int isjs = isns || isie ;
	int isMonthly;
	int nextIsNextMonth;

	curBal = prevBal;
	isMonthly = getenv("gldMonthly") != 0;

	if ( isjs )
		you_include(isie ? "gld/ieChange.h" : "gld/nsChange.h");

	if ( isdev ) {
		if ( prevDate )
			gld_prev(prevDate, prevBal);
		you_include("gld/devTableTag.h");
		if ( n > 0 )
			you_include("gld/devHead.h");
	} else {
		you_include("gld/tableTag.h");
		if ( prevDate )
			gld_prev(prevDate, prevBal);
		you_include(isMonthly ? "gld/monthlyHead.h" : "gld/head.h");
		
	}

	for(i=0;i<n;i++) {
		nextIsNextMonth =
			( i != n-1 && yd_monthOf(gld[i]->date) != yd_monthOf(gld[i+1]->date) );
		gld_line(&curBal, gld[i], i%2, nextIsNextMonth);
		if ( isMonthly && (i == n-1 || nextIsNextMonth ) ) {
			gldMonthlyLine(gld[i]->date, curBal);
			curBal = 0.0;
		}
	}

	if ( isMonthly ) {
		printf("</TABLE>\n");
		return;
		
	}

	gldRepData(uid, fid);

	if ( ! isdev )
		gld_form(fid);

	if ( isdev )
		you_include("gld/devTableEnd.h");
	else
		printf("</TABLE>\n");

	if ( isdev )
		gld_form(fid);

	if ( isjs ) {
		gldSelect(0, uid, fid);
		gldSelect(1, uid, fid);
	}

	if ( isdev ) {
		/* print links to reports rather than reports ??? */
	} else {
		gld_reports(uid, fid);
		gld_send_data(gld, n);
	}
}

/************************************************************/

static
gld_form(fid)
int fid;
{
	int d;
	char sdate[1024];

	you_setenv_int("Fid", fid);

	if ( lastAddDate ) {
		d = lastAddDate;
		sprintf(sdate, "%d %d %d", (d / 100)%100, d%100, d/10000);
		setenv("date", sdate);
	} else
		setenv("date", "");

	setenv("categImg", "youNoImg");
	setenv("toFromImg", "youNoImg");
	if ( you_isNS() || you_isMSIE() ) {
		if ( repnvalues[0] > 0 && ( repnvalues[0] != 1 || *repfvalues[0][0] != 0 ) )
			setenv("categImg", "IMG");
		if ( repnvalues[1] > 0 && ( repnvalues[1] != 1 || *repfvalues[1][0] != 0 ) )
			setenv("toFromImg", "IMG");
	}
	you_include(you_isDevice() ? "gld/devNew.h" : "gld/new.h");
	return(1);
}

/************************************************************/

static
gld_title(File, restrVal)
char *File;
char *restrVal;
{
	char args[1024], href[1024];
	int isdev;

	isdev = you_isDevice();

	if ( isdev ) {
		sprintf(args, "&File=%s", getenv("File"));
		you_href(href, "Open", args);
	} else
		strcpy(href, "javascript:gldNoCat()");

	you_appBar("Cashbook", File, href, restrVal);
}

/************************************************************/

static
gld_js(File, fid, restrFname, restrVal)
char *File;
int fid;
char *restrFname, *restrVal;
{
	char js[1024];
	
	you_jsSrc("gld");
	sprintf(js, "you.gld = new youGld('%s', %d, '%s', '%s');",
		you_jsStr(File),
		fid, 
		restrFname ? you_jsStr(restrFname) : "",
		restrVal ? you_jsStr(restrVal) : ""
		);
	you_js(js);
}

/************************************************************/

static
gld_main(File, uid, fid)
char *File;
int uid, fid;
{
	char s[1024], w[1024];
	char cons[1024];
	int prevDate;
	double prevBal;
	int n;
	GLD *gld[GLD_MAX_ITEMS];
	char restr[1024];
	char *restrFname;
	char *restrVal;
	int isStatement, stCloseDate;
	char sdate[1024], stTitle[1024];
	char *rtitle;
	int isMonthly;
	int isSearch;
	char *searchOp, *searchLeftQuote, *searchRightQuote;;

	if ( fid == 0 ) {
		YOU_ERROR("lost Fid");
		YOU_MSG("Communication Error");
		return(0);
	}
	restrFname = getenv("restrFname");
	restrVal = getenv("restrVal");
	isMonthly = getenv("gldMonthly") != 0;
	searchOp = getenv("searchOp");
	isSearch = ( searchOp != 0 );
	if ( isSearch ) {
		searchLeftQuote = getenv("searchLeftQuote");
		searchRightQuote = getenv("searchRightQuote");
	} else {
		searchOp  = "=" ;
		searchLeftQuote = searchRightQuote = "'" ;
	}

	gld_js(File, fid, restrFname, restrVal);
	isStatement = restrFname && strcmp(restrFname, "Statement") == 0 ;

	stCloseDate = 0;
	if ( isStatement ) {
		stCloseDate = atoi(restrVal);
		eng_intwdate_fmt(sdate, &stCloseDate);
		sprintf(stTitle, "Statement Ending %s", sdate);
		rtitle = stTitle;
	} else if ( isSearch ) {
		sprintf(stTitle, "%s %s %s", restrFname, searchOp, restrVal);
		rtitle = stTitle;
	} else if ( restrVal )
		rtitle = restrVal;
	else if ( isMonthly )
		rtitle = "Month by Month Expenses";
	else
		rtitle = 0;

	gld_title(File, rtitle);

	if ( isMonthly || restrVal && ! isStatement ) {
		prevDate = 0;
		*cons = 0;
	} else {
		prevDate = gld_prevDate(uid, fid, isStatement, stCloseDate) ;
		if ( prevDate == 0 )
			*cons = 0;
		else
			sprintf(cons, "and date > %d", prevDate);
	}

	if ( prevDate )
		prevBal = gld_prevBalance(uid, fid, isStatement, stCloseDate);
	else
		prevBal = 0.0;

	if ( ! restrVal )
		*restr = 0;
	else if ( isStatement )
		sprintf(restr, "and date <= %d", stCloseDate);
	else
		sprintf(restr, "and %s %s %s%s%s",
			restrFname,
			searchOp,
			searchLeftQuote, restrVal, searchRightQuote
			);

	if ( isMonthly ) {
		sprintf(s, "exec gldMonthly %d, %d ", uid, fid);
		n = engdb_getobjects_by_cmd(gld, GLD_MAX_ITEMS, "gld", s);
	} else {
		sprintf(w, "where uid = %d and fid = %d %s %s order by date, entered",
			uid, fid, cons, restr);
		n = engdb_getobjects_by_where(gld, GLD_MAX_ITEMS, "gld", w);
	}
	if ( n < 0 ) {
		YOU_IERROR2(uid, fid, "Can not get data");
		return(0);
	}
	gld_table(uid, fid, prevDate, prevBal, gld, n);
	return(1);
}

/************************************************************/

gld_open(File)
char *File;
{
	int fid;
	int uid;
	char *p;

	p = getenv("Uid");
	uid = atoi(p);


	if ( (fid = fid_open(uid, File)) == 0)
		return(0);

	you_setenv_int("Fid", fid);
	gld_init(uid, fid);

	return(gld_main(File, uid, fid));
}

/************************************************************/

static
gld_sample(uid, fid)
int uid, fid;
{
	GLD g;

	g.uid = uid;
	g.fid = fid;
	strcpy(g.category, "Internet");
	g.date = day_today();
	strcpy(g.toFrom, "max headroom");
	strcpy(g.description, "20 points");
	strcpy(g.ref, "#24");
	g.amount = 20.0;
	g.entered = time(0);

	if ( ! eng_insert(&g, "gld") ) {
		YOU_ERROR0();
		return(0);
	}
	return(1);
}

/************************************************************/

static
gld_init(uid, fid)
int uid, fid;
{
	char thisCmd[1024], cmd[1024];

	sprintf(thisCmd,
		"select count(*) from gld where uid = %d and fid = %d",
		uid, fid);
	sprintf(cmd, "select count(*) from gld where uid = %d", uid);
	if ( engdb_getint(thisCmd) > 0 || engdb_getint(cmd) > 12 )
		return(1);

	return(gld_sample(uid, fid));
}

/************************************************************/

gld_delete()
{
	int uid, fid, eid;
	char cmd[1024];

	youGetEnvUFE(&uid, &fid, &eid);

	/* security implied */
	sprintf(cmd,
		"delete from gld where uid = %d and fid = %d and eng_id = %d",
		uid, fid, eid);
	engdb_sql(cmd);

	gld_main(getenv("File"), uid, fid);
	return(1);
}

/************************************************************/

gld_noOp()
{
	return(gld_main(getenv("File"), atoi(getenv("Uid")), atoi(getenv("Fid"))));
}

/************************************************************/

gld_devShowFile(File)
char *File;
{
	return(gld_open(File));
}

/************************************************************/

/* date,amount,toFrom,category,description,ref,entered,closed */
#define GLD_HS "%-9.9s %-19.19s %-19.19s %-19.19s %-24.24s %-9.9s %-14.14s %-9.9s\n"
#define GLD_DS "%-9.9s %19.2lf %-19s %-19s %-24s %-9s %-14d %-9d\n"

gld_export(File)
char *File;
{
	int fid;
	int uid;
	char w[1024];
	GLD *gld[4096];
	int i, n;
	char *us="-------------------------------------------------------------------------------------------------";

	uid = atoi(getenv("Uid"));
	if ( (fid = fid_fileFid(File)) == 0 )
		return(0);

	sprintf(w, "where uid = %d and fid = %d %s",
		uid, fid, "order by date, entered");
	n = engdb_getobjects_by_where(gld, 4096, "gld", w);
	you_mime("text/plain");
	printf(GLD_HS,
		"Date", "Amount", "To/Form", "Category",
		"Description", "Reference", "Entered", "Closed");
	printf(GLD_HS, us, us, us, us, us, us, us, us);
	for(i=0;i<n;i++)
		printf(GLD_DS,
			you_stdDateFmt(gld[i]->date),
			gld[i]->amount,
			gld[i]->toFrom,
			gld[i]->category,
			gld[i]->description,
			gld[i]->ref,
			gld[i]->entered,
			gld[i]->closed
			);

	return(1);
}

/************************************************************/

gld_monthly()
{
	int uid, fid, eid;
	char cmd[1024];

	youGetEnvUFE(&uid, &fid, &eid);

	you_setenv_int("gldMonthly", 1);
	gld_main(getenv("File"), uid, fid);
	return(1);
}

/************************************************************/
