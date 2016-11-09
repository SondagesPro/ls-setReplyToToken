# set Reply-To (Token)

This allow to set the Reply-To when sending token. Then have different value for From and Reply-To. Reply-To use Bounce email if exist.

## Installation

### Via GIT
- Go to your LimeSurvey Directory (version up to 2.05)
- Clone in plugins/setReplyToToken

## Documentation
If the plugin is activated : before sending a token email : search the Bounce email of the survey, or global is is empty. If Bouce email is valid : use it in Reply-To.

The plugin is tested in 2.05 (build 150413), 2.6.1 (lts), 2.06+ SondagesPro 1.0.27 and 2.55.1. In 2.05 and 2.6.1 (lts) plugin add this features: Add html tag for less spam issue to email and allow use included picture in HTML email content.

## Home page & Copyright

- HomePage <http://extensions.sondages.pro/>
- Copyright © 2016 Valore Formazione Srl <http://www.valoreformazione.it>
- Copyright © 2016 Denis Chenu <http://sondages.pro>
- Licence : GNU General Public License <https://www.gnu.org/licenses/gpl-3.0.html>
