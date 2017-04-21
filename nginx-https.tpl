server {
	include templates/redirect-https.conf;
	server_name {{DOMAINS}};
}

server {
	set $SITEUSER '{{SITEUSER}}';
	include templates/{{TEMPLATE}}-https.conf;
	server_name {{DOMAINS}};
	root {{ROOT}};
}
