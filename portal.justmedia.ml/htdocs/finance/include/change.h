<DIV ID=cbChangeId class=cbChangeClass>
<TABLE BORDER=0>
<FORM onsubmit="cbHideChange();">
<INPUT TYPE=hidden NAME="cbTable" value = "$cbTable">
<INPUT TYPE=hidden NAME="closed">
<INPUT TYPE=hidden NAME="entered">
<INPUT TYPE=hidden NAME="id">
<INPUT TYPE=hidden NAME="t0" value="$msdb_t0">

	<TR>
		<TD> Date </TD>
		<TD> Amount </TD>
		<TD> Paid To/From </TD>
		<TD> Category </TD>
		<TD> Notes </TD>
		<TD></TD>
	</TR>
	<TR>


		<TD><INPUT type=text size=12 maxlength=18 name=date></TD>
		<TD><INPUT type=text size=8 maxlength=30 name=amount></TD>


		<TD>
			<INPUT type=text size=12 maxlength=31 name=toFrom><A HREF="javascript:cbShowSelect('cbSELtoFrom');"
				onMouseOver="msdbSaveMouse();"><IMG BORDER=0 ALT="Select Known Paid-To/From" SRC="images/select.gif"></A>
		</TD>
		<TD>
			<INPUT type=text size=8 maxlength=31 name=category><A HREF="javascript:cbShowSelect('cbSELcategory');"
				onMouseOver="msdbSaveMouse();"><IMG BORDER=0 ALT="Select Known Category" SRC="images/select.gif"></A>
		</TD>




		<TD><INPUT type=text size=12 maxlength=63 name=notes></TD>
		<TD COLSPAN=4 ALIGN=RIGHT>
			<INPUT type=hidden name="EA" value="cbUpdate">
			<!-- lack of a native submit button implies no submitting when hitting return -->
			<INPUT type=submit value="OK">
			<A HREF="javascript:cbHideChange();"><IMG alt="Cancel" BORDER=0 SRC="images/xcorner.gif"></A>
		</TD>



	</TR>

</FORM>
</TABLE>
</DIV>
