========================
B2evolution WXR Exporter 
========================

After struggle with this more than 1 time, I realized that I could migrate b2evolution to wordpress in a easy way.

Wordpress uses a format called WXR (Wordpress eXtended RSS) to deal with importing/exporting and my idea was just extend
a rss skin from b2evolution to have it generating a wordpress WXR compatible file.

Just upload it to your blog an access myblog.com/?tempskin=_wxr . Load the source of the page (unix geeks would use wget!),
save it, rename to something like myblog.wxr, and use the wordpress importer to import your content.
Everything should be fine. I tested it with b2evolution 4, but made some code to make it work with b2evolution 3.

I have tested it on several migrations right now, and everything looks to work OK.
If something fails to you, drop me a note and maybe I could try to help you.
If this was useful to you, maybe you could use paypal to make me a donation.
Use to your own risk (not a lot, cause you're just exporting and exporting content) and be happy!

More info in http://b2evolution.waltercruz.com/tag/wxr