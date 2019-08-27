/* description: Parses end executes mathematical expressions. */

/* lexical grammar */
%lex
%%

\s+                   return 'SPACE'
\S+                   return 'WORD'
[\r\n]+               return 'EOL'
<<EOF>>               return 'EOF'

/lex

/* operator associations and precedence */

//phpOption parserClass:FtpResultParser
//phpOption lexerClass:FtpResultLexer
//phpOption fileName:FtpResultParser.php

%start expressions

%% /* language grammar */

expressions
    : e EOF
        {return $1;}
    | e EOL
        {return $1;}
    ;

e
    : WORD
      {
        $$ = [ $1 ]; //js
		//php $$ = array($1);
      }
    | SPACE
      {
        $$ = [ $1 ]; //js
		//php $$ = array($1);
      }
    | WORD e
      {
        $$ = [$1].concat($2); //js
		//php $$ = array_merge(array($1), $2);
      }
    | SPACE e
      {
        $$ = [$1].concat($2); //js
		//php $$ = array_merge(array($1), $2);
      }
    ;
