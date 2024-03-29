<?php

namespace Amasty\AdminActionsLog\lib;

class JSParser
{
    private $t;
    private $minifier;

    private $opPrecedence = array(
        ';' => 0,
        ',' => 1,
        '=' => 2, '?' => 2, ':' => 2,
        // The above all have to have the same precedence, see bug 330975
        '||' => 4,
        '&&' => 5,
        '|' => 6,
        '^' => 7,
        '&' => 8,
        '==' => 9, '!=' => 9, '===' => 9, '!==' => 9,
        '<' => 10, '<=' => 10, '>=' => 10, '>' => 10, 'in' => 10, 'instanceof' => 10,
        '<<' => 11, '>>' => 11, '>>>' => 11,
        '+' => 12, '-' => 12,
        '*' => 13, '/' => 13, '%' => 13,
        'delete' => 14, 'void' => 14, 'typeof' => 14,
        '!' => 14, '~' => 14, 'U+' => 14, 'U-' => 14,
        '++' => 15, '--' => 15,
        'new' => 16,
        '.' => 17,
        JS_NEW_WITH_ARGS => 0, JS_INDEX => 0, JS_CALL => 0,
        JS_ARRAY_INIT => 0, JS_OBJECT_INIT => 0, JS_GROUP => 0
    );

    private $opArity = array(
        ',' => -2,
        '=' => 2,
        '?' => 3,
        '||' => 2,
        '&&' => 2,
        '|' => 2,
        '^' => 2,
        '&' => 2,
        '==' => 2, '!=' => 2, '===' => 2, '!==' => 2,
        '<' => 2, '<=' => 2, '>=' => 2, '>' => 2, 'in' => 2, 'instanceof' => 2,
        '<<' => 2, '>>' => 2, '>>>' => 2,
        '+' => 2, '-' => 2,
        '*' => 2, '/' => 2, '%' => 2,
        'delete' => 1, 'void' => 1, 'typeof' => 1,
        '!' => 1, '~' => 1, 'U+' => 1, 'U-' => 1,
        '++' => 1, '--' => 1,
        'new' => 1,
        '.' => 2,
        JS_NEW_WITH_ARGS => 2, JS_INDEX => 2, JS_CALL => 2,
        JS_ARRAY_INIT => 1, JS_OBJECT_INIT => 1, JS_GROUP => 1,
        TOKEN_CONDCOMMENT_START => 1, TOKEN_CONDCOMMENT_END => 1
    );

    public function __construct($minifier=null)
    {
        $this->minifier = $minifier;
        $this->t = new JSTokenizer();
    }

    public function parse($s, $f, $l)
    {
        // initialize tokenizer
        $this->t->init($s, $f, $l);

        $x = new JSCompilerContext(false);
        $n = $this->Script($x);
        if (!$this->t->isDone())
            throw new \Exception($this->t->newSyntaxError('Syntax error'));

        return $n;
    }

    private function Script($x)
    {
        $n = $this->Statements($x);
        $n->type = JS_SCRIPT;
        $n->funDecls = $x->funDecls;
        $n->varDecls = $x->varDecls;

        // minify by scope
        if ($this->minifier)
        {
            $n->value = $this->minifier->parseTree($n);

            // clear tree from node to save memory
            $n->treeNodes = null;
            $n->funDecls = null;
            $n->varDecls = null;

            $n->type = JS_MINIFIED;
        }

        return $n;
    }

    private function Statements($x)
    {
        $n = new JSNode($this->t, JS_BLOCK);
        array_push($x->stmtStack, $n);

        while (!$this->t->isDone() && $this->t->peek() != OP_RIGHT_CURLY)
            $n->addNode($this->Statement($x));

        array_pop($x->stmtStack);

        return $n;
    }

    private function Block($x)
    {
        $this->t->mustMatch(OP_LEFT_CURLY);
        $n = $this->Statements($x);
        $this->t->mustMatch(OP_RIGHT_CURLY);

        return $n;
    }

    private function Statement($x)
    {
        $tt = $this->t->get();
        $n2 = null;

        // Cases for statements ending in a right curly return early, avoiding the
        // common semicolon insertion magic after this switch.
        switch ($tt)
        {
            case KEYWORD_FUNCTION:
                return $this->FunctionDefinition(
                    $x,
                    true,
                    count($x->stmtStack) > 1 ? STATEMENT_FORM : DECLARED_FORM
                );
                break;

            case OP_LEFT_CURLY:
                $n = $this->Statements($x);
                $this->t->mustMatch(OP_RIGHT_CURLY);
                return $n;

            case KEYWORD_IF:
                $n = new JSNode($this->t);
                $n->condition = $this->ParenExpression($x);
                array_push($x->stmtStack, $n);
                $n->thenPart = $this->Statement($x);
                $n->elsePart = $this->t->match(KEYWORD_ELSE) ? $this->Statement($x) : null;
                array_pop($x->stmtStack);
                return $n;

            case KEYWORD_SWITCH:
                $n = new JSNode($this->t);
                $this->t->mustMatch(OP_LEFT_PAREN);
                $n->discriminant = $this->Expression($x);
                $this->t->mustMatch(OP_RIGHT_PAREN);
                $n->cases = array();
                $n->defaultIndex = -1;

                array_push($x->stmtStack, $n);

                $this->t->mustMatch(OP_LEFT_CURLY);

                while (($tt = $this->t->get()) != OP_RIGHT_CURLY)
                {
                    switch ($tt)
                    {
                        case KEYWORD_DEFAULT:
                            if ($n->defaultIndex >= 0)
                                throw new \Exception($this->t->newSyntaxError('More than one switch default'));
                        // FALL THROUGH
                        case KEYWORD_CASE:
                            $n2 = new JSNode($this->t);
                            if ($tt == KEYWORD_DEFAULT)
                                $n->defaultIndex = count($n->cases);
                            else
                                $n2->caseLabel = $this->Expression($x, OP_COLON);
                            break;
                        default:
                            throw new \Exception($this->t->newSyntaxError('Invalid switch case'));
                    }

                    $this->t->mustMatch(OP_COLON);
                    $n2->statements = new JSNode($this->t, JS_BLOCK);
                    while (($tt = $this->t->peek()) != KEYWORD_CASE && $tt != KEYWORD_DEFAULT && $tt != OP_RIGHT_CURLY)
                        $n2->statements->addNode($this->Statement($x));

                    array_push($n->cases, $n2);
                }

                array_pop($x->stmtStack);
                return $n;

            case KEYWORD_FOR:
                $n = new JSNode($this->t);
                $n->isLoop = true;
                $this->t->mustMatch(OP_LEFT_PAREN);

                if (($tt = $this->t->peek()) != OP_SEMICOLON)
                {
                    $x->inForLoopInit = true;
                    if ($tt == KEYWORD_VAR || $tt == KEYWORD_CONST)
                    {
                        $this->t->get();
                        $n2 = $this->Variables($x);
                    }
                    else
                    {
                        $n2 = $this->Expression($x);
                    }
                    $x->inForLoopInit = false;
                }

                if ($n2 && $this->t->match(KEYWORD_IN))
                {
                    $n->type = JS_FOR_IN;
                    if ($n2->type == KEYWORD_VAR)
                    {
                        if (count($n2->treeNodes) != 1)
                        {
                            throw new \Exception($this->t->SyntaxError(
                                'Invalid for..in left-hand side',
                                $this->t->filename,
                                $n2->lineno
                            ));
                        }

                        // NB: n2[0].type == IDENTIFIER and n2[0].value == n2[0].name.
                        $n->iterator = $n2->treeNodes[0];
                        $n->varDecl = $n2;
                    }
                    else
                    {
                        $n->iterator = $n2;
                        $n->varDecl = null;
                    }

                    $n->object = $this->Expression($x);
                }
                else
                {
                    $n->setup = $n2 ? $n2 : null;
                    $this->t->mustMatch(OP_SEMICOLON);
                    $n->condition = $this->t->peek() == OP_SEMICOLON ? null : $this->Expression($x);
                    $this->t->mustMatch(OP_SEMICOLON);
                    $n->update = $this->t->peek() == OP_RIGHT_PAREN ? null : $this->Expression($x);
                }

                $this->t->mustMatch(OP_RIGHT_PAREN);
                $n->body = $this->nest($x, $n);
                return $n;

            case KEYWORD_WHILE:
                $n = new JSNode($this->t);
                $n->isLoop = true;
                $n->condition = $this->ParenExpression($x);
                $n->body = $this->nest($x, $n);
                return $n;

            case KEYWORD_DO:
                $n = new JSNode($this->t);
                $n->isLoop = true;
                $n->body = $this->nest($x, $n, KEYWORD_WHILE);
                $n->condition = $this->ParenExpression($x);
                if (!$x->ecmaStrictMode)
                {
                    // <script language="JavaScript"> (without version hints) may need
                    // automatic semicolon insertion without a newline after do-while.
                    // See http://bugzilla.mozilla.org/show_bug.cgi?id=238945.
                    $this->t->match(OP_SEMICOLON);
                    return $n;
                }
                break;

            case KEYWORD_BREAK:
            case KEYWORD_CONTINUE:
                $n = new JSNode($this->t);

                if ($this->t->peekOnSameLine() == TOKEN_IDENTIFIER)
                {
                    $this->t->get();
                    $n->label = $this->t->currentToken()->value;
                }

                $ss = $x->stmtStack;
                $i = count($ss);
                $label = $n->label;
                if ($label)
                {
                    do
                    {
                        if (--$i < 0)
                            throw new \Exception($this->t->newSyntaxError('Label not found'));
                    }
                    while ($ss[$i]->label != $label);
                }
                else
                {
                    do
                    {
                        if (--$i < 0)
                            throw new \Exception($this->t->newSyntaxError('Invalid ' . $tt));
                    }
                    while (!$ss[$i]->isLoop && ($tt != KEYWORD_BREAK || $ss[$i]->type != KEYWORD_SWITCH));
                }

                $n->target = $ss[$i];
                break;

            case KEYWORD_TRY:
                $n = new JSNode($this->t);
                $n->tryBlock = $this->Block($x);
                $n->catchClauses = array();

                while ($this->t->match(KEYWORD_CATCH))
                {
                    $n2 = new JSNode($this->t);
                    $this->t->mustMatch(OP_LEFT_PAREN);
                    $n2->varName = $this->t->mustMatch(TOKEN_IDENTIFIER)->value;

                    if ($this->t->match(KEYWORD_IF))
                    {
                        if ($x->ecmaStrictMode)
                            throw new \Exception($this->t->newSyntaxError('Illegal catch guard'));

                        if (count($n->catchClauses) && !end($n->catchClauses)->guard)
                            throw new \Exception($this->t->newSyntaxError('Guarded catch after unguarded'));

                        $n2->guard = $this->Expression($x);
                    }
                    else
                    {
                        $n2->guard = null;
                    }

                    $this->t->mustMatch(OP_RIGHT_PAREN);
                    $n2->block = $this->Block($x);
                    array_push($n->catchClauses, $n2);
                }

                if ($this->t->match(KEYWORD_FINALLY))
                    $n->finallyBlock = $this->Block($x);

                if (!count($n->catchClauses) && !$n->finallyBlock)
                    throw new \Exception($this->t->newSyntaxError('Invalid try statement'));
                return $n;

            case KEYWORD_CATCH:
            case KEYWORD_FINALLY:
                throw new \Exception($this->t->newSyntaxError($tt + ' without preceding try'));

            case KEYWORD_THROW:
                $n = new JSNode($this->t);
                $n->value = $this->Expression($x);
                break;

            case KEYWORD_RETURN:
                if (!$x->inFunction)
                    throw new \Exception($this->t->newSyntaxError('Invalid return'));

                $n = new JSNode($this->t);
                $tt = $this->t->peekOnSameLine();
                if ($tt != TOKEN_END && $tt != TOKEN_NEWLINE && $tt != OP_SEMICOLON && $tt != OP_RIGHT_CURLY)
                    $n->value = $this->Expression($x);
                else
                    $n->value = null;
                break;

            case KEYWORD_WITH:
                $n = new JSNode($this->t);
                $n->object = $this->ParenExpression($x);
                $n->body = $this->nest($x, $n);
                return $n;

            case KEYWORD_VAR:
            case KEYWORD_CONST:
                $n = $this->Variables($x);
                break;

            case TOKEN_CONDCOMMENT_START:
            case TOKEN_CONDCOMMENT_END:
                $n = new JSNode($this->t);
                return $n;

            case KEYWORD_DEBUGGER:
                $n = new JSNode($this->t);
                break;

            case TOKEN_NEWLINE:
            case OP_SEMICOLON:
                $n = new JSNode($this->t, OP_SEMICOLON);
                $n->expression = null;
                return $n;

            default:
                if ($tt == TOKEN_IDENTIFIER)
                {
                    $this->t->scanOperand = false;
                    $tt = $this->t->peek();
                    $this->t->scanOperand = true;
                    if ($tt == OP_COLON)
                    {
                        $label = $this->t->currentToken()->value;
                        $ss = $x->stmtStack;
                        for ($i = count($ss) - 1; $i >= 0; --$i)
                        {
                            if ($ss[$i]->label == $label)
                                throw new \Exception($this->t->newSyntaxError('Duplicate label'));
                        }

                        $this->t->get();
                        $n = new JSNode($this->t, JS_LABEL);
                        $n->label = $label;
                        $n->statement = $this->nest($x, $n);

                        return $n;
                    }
                }

                $n = new JSNode($this->t, OP_SEMICOLON);
                $this->t->unget();
                $n->expression = $this->Expression($x);
                $n->end = $n->expression->end;
                break;
        }

        if ($this->t->lineno == $this->t->currentToken()->lineno)
        {
            $tt = $this->t->peekOnSameLine();
            if ($tt != TOKEN_END && $tt != TOKEN_NEWLINE && $tt != OP_SEMICOLON && $tt != OP_RIGHT_CURLY)
                throw new \Exception($this->t->newSyntaxError('Missing ; before statement'));
        }

        $this->t->match(OP_SEMICOLON);

        return $n;
    }

    private function FunctionDefinition($x, $requireName, $functionForm)
    {
        $f = new JSNode($this->t);

        if ($f->type != KEYWORD_FUNCTION)
            $f->type = ($f->value == 'get') ? JS_GETTER : JS_SETTER;

        if ($this->t->match(TOKEN_IDENTIFIER))
            $f->name = $this->t->currentToken()->value;
        elseif ($requireName)
            throw new \Exception($this->t->newSyntaxError('Missing function identifier'));

        $this->t->mustMatch(OP_LEFT_PAREN);
        $f->params = array();

        while (($tt = $this->t->get()) != OP_RIGHT_PAREN)
        {
            if ($tt != TOKEN_IDENTIFIER)
                throw new \Exception($this->t->newSyntaxError('Missing formal parameter'));

            array_push($f->params, $this->t->currentToken()->value);

            if ($this->t->peek() != OP_RIGHT_PAREN)
                $this->t->mustMatch(OP_COMMA);
        }

        $this->t->mustMatch(OP_LEFT_CURLY);

        $x2 = new JSCompilerContext(true);
        $f->body = $this->Script($x2);

        $this->t->mustMatch(OP_RIGHT_CURLY);
        $f->end = $this->t->currentToken()->end;

        $f->functionForm = $functionForm;
        if ($functionForm == DECLARED_FORM)
            array_push($x->funDecls, $f);

        return $f;
    }

    private function Variables($x)
    {
        $n = new JSNode($this->t);

        do
        {
            $this->t->mustMatch(TOKEN_IDENTIFIER);

            $n2 = new JSNode($this->t);
            $n2->name = $n2->value;

            if ($this->t->match(OP_ASSIGN))
            {
                if ($this->t->currentToken()->assignOp)
                    throw new \Exception($this->t->newSyntaxError('Invalid variable initialization'));

                $n2->initializer = $this->Expression($x, OP_COMMA);
            }

            $n2->readOnly = $n->type == KEYWORD_CONST;

            $n->addNode($n2);
            array_push($x->varDecls, $n2);
        }
        while ($this->t->match(OP_COMMA));

        return $n;
    }

    private function Expression($x, $stop=false)
    {
        $operators = array();
        $operands = array();
        $n = false;

        $bl = $x->bracketLevel;
        $cl = $x->curlyLevel;
        $pl = $x->parenLevel;
        $hl = $x->hookLevel;

        while (($tt = $this->t->get()) != TOKEN_END)
        {
            if ($tt == $stop &&
                $x->bracketLevel == $bl &&
                $x->curlyLevel == $cl &&
                $x->parenLevel == $pl &&
                $x->hookLevel == $hl
            )
            {
                // Stop only if tt matches the optional stop parameter, and that
                // token is not quoted by some kind of bracket.
                break;
            }

            switch ($tt)
            {
                case OP_SEMICOLON:
                    // NB: cannot be empty, Statement handled that.
                    break 2;

                case OP_HOOK:
                    if ($this->t->scanOperand)
                        break 2;

                    while (	!empty($operators) &&
                        $this->opPrecedence[end($operators)->type] > $this->opPrecedence[$tt]
                    )
                        $this->reduce($operators, $operands);

                    array_push($operators, new JSNode($this->t));

                    ++$x->hookLevel;
                    $this->t->scanOperand = true;
                    $n = $this->Expression($x);

                    if (!$this->t->match(OP_COLON))
                        break 2;

                    --$x->hookLevel;
                    array_push($operands, $n);
                    break;

                case OP_COLON:
                    if ($x->hookLevel)
                        break 2;

                    throw new \Exception($this->t->newSyntaxError('Invalid label'));
                    break;

                case OP_ASSIGN:
                    if ($this->t->scanOperand)
                        break 2;

                    // Use >, not >=, for right-associative ASSIGN
                    while (	!empty($operators) &&
                        $this->opPrecedence[end($operators)->type] > $this->opPrecedence[$tt]
                    )
                        $this->reduce($operators, $operands);

                    array_push($operators, new JSNode($this->t));
                    end($operands)->assignOp = $this->t->currentToken()->assignOp;
                    $this->t->scanOperand = true;
                    break;

                case KEYWORD_IN:
                    // An in operator should not be parsed if we're parsing the head of
                    // a for (...) loop, unless it is in the then part of a conditional
                    // expression, or parenthesized somehow.
                    if ($x->inForLoopInit && !$x->hookLevel &&
                        !$x->bracketLevel && !$x->curlyLevel &&
                        !$x->parenLevel
                    )
                        break 2;
                // FALL THROUGH
                case OP_COMMA:
                    // A comma operator should not be parsed if we're parsing the then part
                    // of a conditional expression unless it's parenthesized somehow.
                    if ($tt == OP_COMMA && $x->hookLevel &&
                        !$x->bracketLevel && !$x->curlyLevel &&
                        !$x->parenLevel
                    )
                        break 2;
                // Treat comma as left-associative so reduce can fold left-heavy
                // COMMA trees into a single array.
                // FALL THROUGH
                case OP_OR:
                case OP_AND:
                case OP_BITWISE_OR:
                case OP_BITWISE_XOR:
                case OP_BITWISE_AND:
                case OP_EQ: case OP_NE: case OP_STRICT_EQ: case OP_STRICT_NE:
                case OP_LT: case OP_LE: case OP_GE: case OP_GT:
                case KEYWORD_INSTANCEOF:
                case OP_LSH: case OP_RSH: case OP_URSH:
                case OP_PLUS: case OP_MINUS:
                case OP_MUL: case OP_DIV: case OP_MOD:
                case OP_DOT:
                    if ($this->t->scanOperand)
                        break 2;

                    while (	!empty($operators) &&
                        $this->opPrecedence[end($operators)->type] >= $this->opPrecedence[$tt]
                    )
                        $this->reduce($operators, $operands);

                    if ($tt == OP_DOT)
                    {
                        $this->t->mustMatch(TOKEN_IDENTIFIER);
                        array_push($operands, new JSNode($this->t, OP_DOT, array_pop($operands), new JSNode($this->t)));
                    }
                    else
                    {
                        array_push($operators, new JSNode($this->t));
                        $this->t->scanOperand = true;
                    }
                    break;

                case KEYWORD_DELETE: case KEYWORD_VOID: case KEYWORD_TYPEOF:
                case OP_NOT: case OP_BITWISE_NOT: case OP_UNARY_PLUS: case OP_UNARY_MINUS:
                case KEYWORD_NEW:
                    if (!$this->t->scanOperand)
                        break 2;

                    array_push($operators, new JSNode($this->t));
                    break;

                case OP_INCREMENT: case OP_DECREMENT:
                if ($this->t->scanOperand)
                {
                    array_push($operators, new JSNode($this->t));  // prefix increment or decrement
                }
                else
                {
                    // Don't cross a line boundary for postfix {in,de}crement.
                    $t = $this->t->tokens[($this->t->tokenIndex + $this->t->lookahead - 1) & 3];
                    if ($t && $t->lineno != $this->t->lineno)
                        break 2;

                    if (!empty($operators))
                    {
                        // Use >, not >=, so postfix has higher precedence than prefix.
                        while ($this->opPrecedence[end($operators)->type] > $this->opPrecedence[$tt])
                            $this->reduce($operators, $operands);
                    }

                    $n = new JSNode($this->t, $tt, array_pop($operands));
                    $n->postfix = true;
                    array_push($operands, $n);
                }
                break;

                case KEYWORD_FUNCTION:
                    if (!$this->t->scanOperand)
                        break 2;

                    array_push($operands, $this->FunctionDefinition($x, false, EXPRESSED_FORM));
                    $this->t->scanOperand = false;
                    break;

                case KEYWORD_NULL: case KEYWORD_THIS: case KEYWORD_TRUE: case KEYWORD_FALSE:
                case TOKEN_IDENTIFIER: case TOKEN_NUMBER: case TOKEN_STRING: case TOKEN_REGEXP:
                if (!$this->t->scanOperand)
                    break 2;

                array_push($operands, new JSNode($this->t));
                $this->t->scanOperand = false;
                break;

                case TOKEN_CONDCOMMENT_START:
                case TOKEN_CONDCOMMENT_END:
                    if ($this->t->scanOperand)
                        array_push($operators, new JSNode($this->t));
                    else
                        array_push($operands, new JSNode($this->t));
                    break;

                case OP_LEFT_BRACKET:
                    if ($this->t->scanOperand)
                    {
                        // Array initialiser.  Parse using recursive descent, as the
                        // sub-grammar here is not an operator grammar.
                        $n = new JSNode($this->t, JS_ARRAY_INIT);
                        while (($tt = $this->t->peek()) != OP_RIGHT_BRACKET)
                        {
                            if ($tt == OP_COMMA)
                            {
                                $this->t->get();
                                $n->addNode(null);
                                continue;
                            }

                            $n->addNode($this->Expression($x, OP_COMMA));
                            if (!$this->t->match(OP_COMMA))
                                break;
                        }

                        $this->t->mustMatch(OP_RIGHT_BRACKET);
                        array_push($operands, $n);
                        $this->t->scanOperand = false;
                    }
                    else
                    {
                        // Property indexing operator.
                        array_push($operators, new JSNode($this->t, JS_INDEX));
                        $this->t->scanOperand = true;
                        ++$x->bracketLevel;
                    }
                    break;

                case OP_RIGHT_BRACKET:
                    if ($this->t->scanOperand || $x->bracketLevel == $bl)
                        break 2;

                    while ($this->reduce($operators, $operands)->type != JS_INDEX)
                        continue;

                    --$x->bracketLevel;
                    break;

                case OP_LEFT_CURLY:
                    if (!$this->t->scanOperand)
                        break 2;

                    // Object initialiser.  As for array initialisers (see above),
                    // parse using recursive descent.
                    ++$x->curlyLevel;
                    $n = new JSNode($this->t, JS_OBJECT_INIT);
                    while (!$this->t->match(OP_RIGHT_CURLY))
                    {
                        do
                        {
                            $tt = $this->t->get();
                            $tv = $this->t->currentToken()->value;
                            if (($tv == 'get' || $tv == 'set') && $this->t->peek() == TOKEN_IDENTIFIER)
                            {
                                if ($x->ecmaStrictMode)
                                    throw new \Exception($this->t->newSyntaxError('Illegal property accessor'));

                                $n->addNode($this->FunctionDefinition($x, true, EXPRESSED_FORM));
                            }
                            else
                            {
                                switch ($tt)
                                {
                                    case TOKEN_IDENTIFIER:
                                    case TOKEN_NUMBER:
                                    case TOKEN_STRING:
                                        $id = new JSNode($this->t);
                                        break;

                                    case OP_RIGHT_CURLY:
                                        if ($x->ecmaStrictMode)
                                            throw new \Exception($this->t->newSyntaxError('Illegal trailing ,'));
                                        break 3;

                                    default:
                                        throw new \Exception($this->t->newSyntaxError('Invalid property name'));
                                }

                                $this->t->mustMatch(OP_COLON);
                                $n->addNode(new JSNode($this->t, JS_PROPERTY_INIT, $id, $this->Expression($x, OP_COMMA)));
                            }
                        }
                        while ($this->t->match(OP_COMMA));

                        $this->t->mustMatch(OP_RIGHT_CURLY);
                        break;
                    }

                    array_push($operands, $n);
                    $this->t->scanOperand = false;
                    --$x->curlyLevel;
                    break;

                case OP_RIGHT_CURLY:
                    if (!$this->t->scanOperand && $x->curlyLevel != $cl)
                        throw new \Exception('PANIC: right curly botch');
                    break 2;

                case OP_LEFT_PAREN:
                    if ($this->t->scanOperand)
                    {
                        array_push($operators, new JSNode($this->t, JS_GROUP));
                    }
                    else
                    {
                        while (	!empty($operators) &&
                            $this->opPrecedence[end($operators)->type] > $this->opPrecedence[KEYWORD_NEW]
                        )
                            $this->reduce($operators, $operands);

                        // Handle () now, to regularize the n-ary case for n > 0.
                        // We must set scanOperand in case there are arguments and
                        // the first one is a regexp or unary+/-.
                        $n = end($operators);
                        $this->t->scanOperand = true;
                        if ($this->t->match(OP_RIGHT_PAREN))
                        {
                            if ($n && $n->type == KEYWORD_NEW)
                            {
                                array_pop($operators);
                                $n->addNode(array_pop($operands));
                            }
                            else
                            {
                                $n = new JSNode($this->t, JS_CALL, array_pop($operands), new JSNode($this->t, JS_LIST));
                            }

                            array_push($operands, $n);
                            $this->t->scanOperand = false;
                            break;
                        }

                        if ($n && $n->type == KEYWORD_NEW)
                            $n->type = JS_NEW_WITH_ARGS;
                        else
                            array_push($operators, new JSNode($this->t, JS_CALL));
                    }

                    ++$x->parenLevel;
                    break;

                case OP_RIGHT_PAREN:
                    if ($this->t->scanOperand || $x->parenLevel == $pl)
                        break 2;

                    while (($tt = $this->reduce($operators, $operands)->type) != JS_GROUP &&
                        $tt != JS_CALL && $tt != JS_NEW_WITH_ARGS
                    )
                    {
                        continue;
                    }

                    if ($tt != JS_GROUP)
                    {
                        $n = end($operands);
                        if ($n->treeNodes[1]->type != OP_COMMA)
                            $n->treeNodes[1] = new JSNode($this->t, JS_LIST, $n->treeNodes[1]);
                        else
                            $n->treeNodes[1]->type = JS_LIST;
                    }

                    --$x->parenLevel;
                    break;

                // Automatic semicolon insertion means we may scan across a newline
                // and into the beginning of another statement.  If so, break out of
                // the while loop and let the t.scanOperand logic handle errors.
                default:
                    break 2;
            }
        }

        if ($x->hookLevel != $hl)
            throw new \Exception($this->t->newSyntaxError('Missing : in conditional expression'));

        if ($x->parenLevel != $pl)
            throw new \Exception($this->t->newSyntaxError('Missing ) in parenthetical'));

        if ($x->bracketLevel != $bl)
            throw new \Exception($this->t->newSyntaxError('Missing ] in index expression'));

        if ($this->t->scanOperand)
            throw new \Exception($this->t->newSyntaxError('Missing operand'));

        // Resume default mode, scanning for operands, not operators.
        $this->t->scanOperand = true;
        $this->t->unget();

        while (count($operators))
            $this->reduce($operators, $operands);

        return array_pop($operands);
    }

    private function ParenExpression($x)
    {
        $this->t->mustMatch(OP_LEFT_PAREN);
        $n = $this->Expression($x);
        $this->t->mustMatch(OP_RIGHT_PAREN);

        return $n;
    }

    // Statement stack and nested statement handler.
    private function nest($x, $node, $end = false)
    {
        array_push($x->stmtStack, $node);
        $n = $this->statement($x);
        array_pop($x->stmtStack);

        if ($end)
            $this->t->mustMatch($end);

        return $n;
    }

    private function reduce(&$operators, &$operands)
    {
        $n = array_pop($operators);
        $op = $n->type;
        $arity = $this->opArity[$op];
        $c = count($operands);
        if ($arity == -2)
        {
            // Flatten left-associative trees
            if ($c >= 2)
            {
                $left = $operands[$c - 2];
                if ($left->type == $op)
                {
                    $right = array_pop($operands);
                    $left->addNode($right);
                    return $left;
                }
            }
            $arity = 2;
        }

        // Always use push to add operands to n, to update start and end
        $a = array_splice($operands, $c - $arity);
        for ($i = 0; $i < $arity; $i++)
            $n->addNode($a[$i]);

        // Include closing bracket or postfix operator in [start,end]
        $te = $this->t->currentToken()->end;
        if ($n->end < $te)
            $n->end = $te;

        array_push($operands, $n);

        return $n;
    }
}
