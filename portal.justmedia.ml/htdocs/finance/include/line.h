<TR class=$cbLineClass>
	<TD> $date</TD>
	<TD ALIGN=RIGHT $negAmountClass> $amount </TD>
	<TD> <A HREF="javascript:cbRestrictView(1, '$toFrom');">$toFrom</A> </TD>
	<TD> <A HREF="javascript:cbRestrictView(0, '$category');">$category</A> </TD>
	<TD> $notes</TD>
	<TD ALIGN=RIGHT $negBalClass> $balance </TD>
	<TD>
		<A HREF="javascript:cbCmd('cbChange', $id, '')" onMouseOver="msdbMover('Edit this Entry');" onMouseOut="msdbMout();">Edit</A>
	</TD>
	<TD>
		<A HREF="javascript:cbCmd('cbNew', $id, '')" onMouseOver="msdbMover('Edit a new Copy');" onMouseOut="msdbMout();">Copy</A>
	</TD>
	<TD>
		<A HREF="javascript:cbCmd('cbCons', $id, '')" onMouseOver="msdbMover('Hide Past Entries');" onMouseOut="msdbMout();">Fold</A>
	</TD>
	<TD>
		<A HREF="javascript:cbCmd('cbDelete', $id, '')" onMouseOver="msdbMover('Remove this Entry');" onMouseOut="msdbMout();">Delete</A>
	</TD>
</TR>
