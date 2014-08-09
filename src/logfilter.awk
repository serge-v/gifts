BEGIN{
	level_id["TRACE"] = 50
	level_id["DEBUG"] = 100
	level_id["INFO"] = 200
	level_id["WARN"] = 300
	level_id["ERROR"] = 400
	level_id["FAIL"] = 500
	cnt = 0
	print "<html><body><pre>"
}

cnt > 1000{
	exit
}

{
	lvl = level_id[$3]
	
	if (filter == "err" && lvl < 400)
	{
		next
	}
	
	if (filter == "info" && lvl < 200)
	{
		next
	}
	
	if (filter != "err" && lvl < 200)
	{
		print "<font color=\"gray\">" $0 "</font>"
	}
	else if (filter != "err" && lvl == 300)
	{
		print "<font color=\"magenta\">" $0 "</font>"
	}
	else if (filter != "err" && lvl > 300)
	{
		print "<font color=\"red\">" $0 "</font>"
	}
	else
	{
		print $0
	}
	
	cnt++
}

END{
	print "</pre></body></html>"
}