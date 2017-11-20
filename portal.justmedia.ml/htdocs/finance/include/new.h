<FORM NAME=cbNewForm>

	<TR class=cbNew>
		<TD><INPUT type=text size=12 maxlength=18 name=date value="$date"></TD>
		<TD><INPUT type=text size=8 maxlength=30 name=amount value=""></TD>
		<TD>
			<INPUT type=text size=12 maxlength=31 name=toFrom value=""><A HREF="javascript:cbShowSelect('cbSELtoFrom');"
				onMouseOver="msdbSaveMouse();"><$toFromImg BORDER=0 ALT="Select Known Paid-To/From" SRC="images/select.gif"></A>
		</TD>
		<TD>
			<INPUT type=text size=8 maxlength=31 name=category value=""><A HREF="javascript:cbShowSelect('cbSELcategory');"
				onMouseOver="msdbSaveMouse();"><$categImg BORDER=0 ALT="Select Known Category" SRC="images/select.gif"></A>
		</TD>
		<TD><INPUT type=text size=12 maxlength=63 name=notes value=""></TD>
		<TD COLSPAN=5 ALIGN=RIGHT>
			<INPUT TYPE=hidden NAME="t0" value="$msdb_t0">
			<INPUT type=hidden name="EA" value="cbAdd">
			<INPUT TYPE=hidden NAME="cbTable" value = "$cbTable">
			<INPUT type=submit value="New">
		</TD>
	</TR>

</FORM>
<SCRIPT LANGUAGE="JavaScript">
	document.forms['cbNewForm'].date.select();
	document.forms['cbNewForm'].date.focus();
</SCRIPT>
