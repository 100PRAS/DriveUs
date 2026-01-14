<%EnableSessionState=False
host = Request.ServerVariables("HTTP_HOST")

if host = "driveus.eu" or host = "www.driveus.eu" then response.redirect("https://www.driveus.eu/")

else
response.redirect("https://www.driveus.eu/error.htm")
end if
%>