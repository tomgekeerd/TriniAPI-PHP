# TriniAPI-PHP

This is the API I made for my school 'Trinitas College'. I mainly use this handmade API for the iOS Application I'm currently making for my PWS (READ: Last final assignment for graduation on highschool in Holland).

# The API

The API is pretty boring, it has looked very bad before but I am currently in a state that I want great looking code. I built it using the Slim Framework to receive HTTP requests. I am basically logging in into the web portal of our school, after that I'm getting some website (Schedule, number, exam_numbers) using cURL and I do some (HTML) parsing after which I try to give a nice, JSON Wrapped response to the user.

# Technology

This is basically made to made request much easier to parse in the eventual iOS Application so I don't have to this in the front-end.

# Problems

There is still one bug in the API, in the schedule request. Where when I want to get a page for the first time in a while (probably when a cookie expired) it doesn't load the first time. I get the HTTP code 202. I tried to do a loop of cURL request which didn't work either. Or I do this last trick in the iOS Application, or I will still dive in to it soon.

# Excercise

If you kind of look at our school's webportal you may notice there is no system at all except for the schedule JSON file. The main excercise for this API was then making something from really nothing by parsing HTML and super buggy things and trying to make it stable again.
