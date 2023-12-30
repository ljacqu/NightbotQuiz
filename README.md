# Nightbot quiz

Multi-tenant quiz management system using MySQL, written in PHP. It allows people to manage a quiz
for their Twitch stream; users can interact with the quiz via Nightbot, a bot for Twitch.

## Screenshots
Real examples with changed usernames and any irrelevant chat removed.

![Screenshot](https://raw.githubusercontent.com/ljacqu/NightbotQuiz/langs/screenshots/demo.png)
   ![Screenshot](https://raw.githubusercontent.com/ljacqu/NightbotQuiz/langs/screenshots/demo2.png)

## Setup
- Change Configuration.php with your database details
- Run init.php to initialize the database and the first user

## Configuration
The questions of the quiz come from external sources so that non-technical people can define them. Question 
definitions  are created and updated by using the "Update questions" page within the administration; the 
sources and the  creation logic is bound to the quiz owner's name. To add a new owner, classes in the
subfolder `owner/` must  be created and the factory functions in `Updater` and `HtmlPageGenerator` must
be extended.

Questions have a question type: for a new type of question, a new question type class needs to be provided—see
`inc/questiontype/`. Question types have no state but define the behavior of all questions of their type.

## Serving a quiz
Command definitions that must be added to Nightbot are provided in the overview page of the quiz's
administration.

### Timer
You can define a timer in Nightbot to run `!q timer` so that the current question is solved and a new question
is served regularly. However, because the shortest interval possible is 5 minutes, it is difficult to fine-tune
various timings.

For this purpose, the administration area has its own timer that you can keep open in a browser tab. It runs
`!q timer` every 15 seconds. In order to use it, you have to register a client in Nightbot and then connect
that client with Nightbot. The subpages in "Timer configuration" contain all necessary instructions.

## Terminology
- A _question_  in the code is a question definition
- A (question) _draw_ is a reference to a question that was _drawn_ as the current question at some point.
  Users provide answers for the current _draw_.
- The owner is the _quiz owner_, which would more traditionally be called a tenant.

## Updating the app
- `update.sh` gets the most recent files from master and updates them, except for `Configuration.php` 
  and files in `gen/`. If more files are added to `gen/`, the update script should be extended.
  This script exists because I can't link the app with this repository.
- Database changes are included in the initialization script; changes since the first productive version
  are included as migration scripts in `inc/migrations/`. These scripts have to be run manually.

This is fine-tuned for my current operating setup, so please create an issue if you plan on using this so
we can remove things that are too specific and clarify maintenance processes.
