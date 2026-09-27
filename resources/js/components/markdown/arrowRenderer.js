export function getArrowMarkdownRenderer() {

	return {
		name: 'arrowList',
		level: 'block',                                 // Is this a block-level or inline-level tokenizer?
		start(src) {
			return src.match(/^\s*(--?|==?)>/)?.index;
		},
		tokenizer(src, _tokens) {
			const rule  = /^(-{1,2}|={1,2})>\s*([^\n]*(?:\n|$))/
			const match = rule.exec(src);
			if (match) {

				const arrowUnicode = match[1][0] === '-' ? 8594 : 8680;

				const token = {                        // Token to generate
					type: 'arrowList',                  // Should match "name" above
					raw: match[0],                      // Text to consume from the source
					text: match[2],
					unicode: arrowUnicode,
					tokens: []                          // Additional custom properties, including any further-nested inline tokens
				};

				this.lexer.inline(token.text, token.tokens);    // Queue this data to be processed for inline tokens
				return token;
			}
		},
		renderer(token) {
			return `<p class="summary"><span>&#${token.unicode};</span> ${this.parser.parseInline(token.tokens)}</p>\n`
		},
		childTokens: [],                 // Any child tokens to be visited by walkTokens
	};
}
