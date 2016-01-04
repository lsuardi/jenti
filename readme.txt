
TODO

- how session works
  0. user info cookies expire every day
  1. init user info from cookies
  2. if user email available (email cookie set on login for 1 year)
  2.1 update cookies with info from database
  2.2 set database update flag (do only once a day)

- add message YOUR SCORE IS NOT SAVED UNTIL YOU REGISTER
- need JentiSession->format_date_for_strtotime( date, format )
- word guerra does not load definition 5 because of overlapping short desc
- count guesses, popular words
- create table with play tables word totals
- show how many users
- ajax error response
- convert config and catalog to class
- JentiSession->get_variable() and set_variable()
- put source info in separate table
- source may return words from many languages
- how to play translation, multi language game
- count tag frequency
- crawler keeps maintaining the dictionary
  for each available data source
    find words that have no definition for data source
      get definition from data source
        update word (tags?), create definition
- need list of data sources
- tag words with minimum age required, easier game for younger kids



- calculate tag frequency
SELECT WT.TAG, WT.LANGUAGE_CODE, COUNT(WD.ID)
FROM WORD_TAG WT, WORD_DEFINITION WD
WHERE WD.TAGS LIKE CONCAT('%(', WT.TAG, ')%')
GROUP BY WT.TAG, WT.LANGUAGE_CODE
ORDER BY 3 DESC;

- some definitions are empty
select * 
from word wo, word_definition wd
where wo.id = wd.word_id
and trim(definition) like '';