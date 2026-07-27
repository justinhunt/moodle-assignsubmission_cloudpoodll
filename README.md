# Cloud Poodll Assignment Submission for Moodle

**Adds audio, video and whiteboard recording as a native Moodle Assignment submission type.**

Students record their submission directly in the browser using a Cloud Poodll recorder — no
separate app, no large upload. Recordings are processed and stored in the Cloud Poodll cloud, so
your Moodle server and course backups stay small. Because it's a real Moodle assignment
submission plugin (not a LTI or external activity), it works with Moodle's normal grading workflow,
assignment feedback, and portfolio export.

- **Plugin:** `assignsubmission_cloudpoodll` (assignment submission subplugin)
- **Maintainer:** Justin Hunt — poodllsupport@gmail.com
- **Documentation:** https://support.poodll.com
- **License:** GNU GPL v3 or later

---

## Contents

- [Requirements](#requirements)
- [Installation](#installation)
- [Site configuration](#site-configuration)
- [Recorders and skins](#recorders-and-skins)
- [Transcription, transcoding and playback](#transcription-transcoding-and-playback)
- [Using it on an assignment](#using-it-on-an-assignment)
- [Privacy](#privacy)
- [Support](#support)

---

## Requirements

| | |
|---|---|
| Moodle | 4.3 or later (`$plugin->requires = 2023100900`) |
| PHP | 8.1+ (PHP 8.4 supported) |
| Database | MySQL / MariaDB / PostgreSQL |
| Cloud Poodll account | **Required.** An API user and secret from https://poodll.com |

Cloud Poodll relies on the Cloud Poodll service for recording, storage, transcoding and speech
transcription. Without API credentials the submission type will not function.
See [Cloud Poodll API secret](https://support.poodll.com/support/solutions/articles/19000083076-cloud-poodll-api-secret)
for how to obtain them.

## Installation

1. Copy the plugin folder to `mod/assign/submission/cloudpoodll` in your Moodle code root (on
   Moodle 5.0+ this is `public/mod/assign/submission/cloudpoodll`).
2. Visit **Site administration → Notifications** and complete the upgrade.
3. Enter your Cloud Poodll API user and secret at
   **Site administration → Plugins → Assignment submission plugins → Cloud Poodll Submissions**.

## Site configuration

Settings live under **Site administration → Plugins → Assignment submission plugins → Cloud
Poodll Submissions**:

- **API user / API secret**, 
- **AWS region**  The AWS region processing and data storage take place in, 
- **Cloud Poodll server** The default is fine. Users with AWS region Ningxia in China should use cloud.poodll.cn
- **Enabled by default** — turn this submission type on for all new assignments automatically.
- Separate **audio** and **video** settings groups controlling default recorder, player
  appearance (single view and list view: shown inline, in a lightbox, as a link, or not at all),
  and player size.
- **Transcription**, **transcoding** (to mp3/mp4) and **secure playback** (time-limited playback
  URLs) can each be enabled independently.
- **Fallback behaviour** for browsers without HTML5 recording support: fall back to a file
  upload screen, an iOS-specific upload screen, or just a warning.
- **Show local file uploader** — let students upload an existing recording alongside (or instead
  of) recording live.
- Expiry days for recordings, and a custom name for the submission field.

## Recorders and skins

Three recorder types are available: **audio**, **video**, and **whiteboard** (a drawing
surface with an optional background image, sized via the Whiteboard size setting). Each comes in
a choice of visual skins so the recorder isn't a one-size-fits-all widget.

## Transcription, transcoding and playback

- **Transcription** — Cloud Poodll can transcribe what the student says and display it beneath
  the player (and as captions), speeding up grading and helping language learners see their own
  speech written down. Transcripts can also be run through AI grammar correction.
- **Transcoding** — recordings can be converted to mp3/mp4 for broad compatibility.
- **Player type** — play back with a plain player, or one that shows an interactive or standard
  transcript alongside it.


## Using it on an assignment

Once enabled (site-wide by default, or per-assignment), the Cloud Poodll recorder is displayed to students in the assignment submission page. Recordings go through the
standard Moodle Assignment grading screen, so feedback, grades and portfolio export all work as
they would for any other assignment submission.

Typical uses: asynchronous oral presentations (so students don't need to present live), and
language-learning speaking practice — self-introductions, short monologues, or unscripted
conversations submitted for teacher assessment.

## Privacy

This plugin stores personal data: submission recordings and, if enabled, transcripts.
Recordings are stored via Cloud Poodll (AWS S3), and the hash of the Moodle username appears in recording
URLs. The plugin implements the Moodle Privacy API for export and deletion.

## Support

- Documentation and how-tos: https://support.poodll.com
- Account and subscriptions: https://member.poodll.com
- Contact: poodllsupport@gmail.com

## License

Copyright Justin Hunt / Poodll. Licensed under the
[GNU GPL v3 or later](http://www.gnu.org/copyleft/gpl.html).
