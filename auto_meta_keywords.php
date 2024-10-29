<?php
/**
 * Plugin Name:       Auto Meta Keywords
 * Plugin URI:        https://www.mansurahamed.com/auto-meta-keywords/
 * Description:       Automatically gets the keywords of your content and shows them in the meta keywords tag. Meta keywords tag can be used when determining the page relevance to search queries by the search engines. 
 * Version:           1.0.3
 * Author:            mansurahamed
 * Author URI:        https://www.upwork.com/freelancers/~013259d08861bd5bd8
 * Text Domain:       auto-meta-keywords
 */


if(!class_exists('AutoMetaKeywords'))
{
	class AutoMetaKeywords
	{
		/**
		 * Hook into the appropriate actions when the class is constructed.
		 */
		public function __construct() {
			add_action( 'save_post', array( $this, 'save_meta_keywords' ), 10, 3 );
			add_action( 'wp_head', array( $this, 'add_meta_keywords' ),2 );
		}
		
		/**
		 * Saves keywords in post meta :: This doesn't effect site loading speed
		 */
		public function save_meta_keywords( $post_id, $post, $update) {
			$content = $post->post_content;
			$pattern = '/\[mwai_.*?\]/';
			$content = preg_replace( $pattern, '', $content );
			$content = apply_filters( 'the_content', $content );
			$content = $this->cleanText( $content );
			$content = $this->cleanSentences( $content );
			$keywords = $this->extract_keywords($content);
			$keywords = array_slice($keywords, 0, apply_filters('amk_max_keywords',10));
			$keywords = implode(',',$keywords);
			update_post_meta($post_id, 'amk_meta_keywords', $keywords);
		}
		
		function cleanText( $rawText = "" ) {
		$text = html_entity_decode( $rawText );
		$text = wp_strip_all_tags( $text );
		$text = preg_replace( '/[\r\n]+/', "\n", $text );
		return $text . " ";
	}
	
		function cleanSentences( $text ) {
			$maxTokens = 2000;
			$sentences = preg_split('/(?<=[.?!。．！？])+/u', $text);
			$hashes = array();
			$uniqueSentences = array();
			$length = 0;
			foreach ( $sentences as $sentence ) {
				$sentence = preg_replace( '/^[\pZ\pC]+|[\pZ\pC]+$/u', '', $sentence );
				$hash = md5( $sentence );
				if ( !in_array( $hash, $hashes ) ) {
						$tokensCount = 0;
				if ( $length + $tokensCount > $maxTokens ) {
				  continue;
				}
				$hashes[] = $hash;
				$uniqueSentences[] = $sentence;
				$length += $tokensCount;
			  }
			}
			$freshText = implode( " ", $uniqueSentences );
			$freshText = preg_replace( '/^[\pZ\pC]+|[\pZ\pC]+$/u', '', $freshText );
			return $freshText;
  	}
		

		/**
		 * Adds the meta tag keywords.
		 */
		public function add_meta_keywords( $post_type ) {
			global $post;
			$meta_keywords;
			$meta_keywords = get_post_meta($post->ID, 'amk_meta_keywords', true);
			if(!$meta_keywords && apply_filters('amk_auto_frontend', true))
			{
				$content = $post->post_content;
				$keywords = $this->extract_keywords($content);
				$keywords = array_slice($keywords, 0, apply_filters('amk_max_keywords',10));
				$meta_keywords = implode(',',$keywords);
			}
			$meta_keywords = apply_filters('amk_custom_keywords',$meta_keywords);
?>
<meta name="keywords" content="<?php echo esc_attr($meta_keywords) ?>" />
<?php
		}
		
		/**
		 * Extracts keywords from a post/page content
		 */
		function extract_keywords($str)
		{
			$minWordLen = apply_filters('amk_min_word_length',4);
			$minWordOccurrences = apply_filters('amk_min_word_occurance',1);
			$str = preg_replace('/[^\p{L}0-9 ]/', ' ', $str);
			$str = trim(preg_replace('/\s+/', ' ', $str));

			$words = explode(' ', $str);
			$keywords = array();
			while(($c_word = array_shift($words)) !== null)
			{
				if(strlen($c_word) < $minWordLen) continue;

				if(array_key_exists($c_word, $keywords)) $keywords[$c_word][1]++;
				else $keywords[$c_word] = array($c_word, 1);
			}
			usort($keywords, array(&$this,'keyword_count_sort'));
			$final_keywords = array();
			foreach($keywords as $keyword_det)
			{
				if($keyword_det[1] < $minWordOccurrences) break;
				$final_keywords[] = $keyword_det[0];
			}
			$str = 'a, about, above, across, after, afterwards, again, against, all, almost, alone, along, already, also, although, always, am, among, amongst, amoungst, amount, an, and, another, any, anyhow, anyone, anything, anyway, anywhere, are, around, as, at, back, be, became, because, become, becomes, becoming, been, before, beforehand, behind, being, below, beside, besides, between, beyond, bill, both, bottom, but, by, call, can, cannot, cant, co, con, could, couldnt, cry, de, describe, detail, do, done, down, due, during, each, eg, eight, either, eleven, else, elsewhere, empty, enough, etc, even, ever, every, everyone, everything, everywhere, except, few, fifteen, fifty, fill, find, fire, first, five, for, former, formerly, forty, found, four, from, front, full, further, get, give, go, had, has, hasnt, have, he, hence, her, here, hereafter, hereby, herein, hereupon, hers, herself, him, himself, his, how, however, hundred, ie, if, in, inc, indeed, interest, into, is, it, its, itself, keep, last, latter, latterly, least, less, ltd, made, many, may, me, meanwhile, might, mill, mine, more, moreover, most, mostly, move, much, must, my, myself, name, namely, neither, never, nevertheless, next, nine, no, nobody, none, noone, nor, not, nothing, now, nowhere, of, off, often, on, once, one, only, onto, or, other, others, otherwise, our, ours, ourselves, out, over, own, part, per, perhaps, please, put, rather, re, same, see, seem, seemed, seeming, seems, serious, several, she, should, show, side, since, sincere, six, sixty, so, some, somehow, someone, something, sometime, sometimes, somewhere, still, such, system, take, ten, than, that, the, their, them, themselves, then, thence, there, thereafter, thereby, therefore, therein, thereupon, these, they, thickv, thin, third, this, those, though, three, through, throughout, thru, thus, to, together, too, top, toward, towards, twelve, twenty, two, un, under, until, up, upon, us, very, via, was, we, well, were, what, whatever, when, whence, whenever, where, whereafter, whereas, whereby, wherein, whereupon, wherever, whether, which, while, whither, who, whoever, whole, whom, whose, why, will, with, within, without, would, yet, you, your, yours, yourself, yourselves';
			 $common = explode(', ',$str);

			for($i=0;$i<count($final_keywords);$i++)
			{
				foreach($common as $word)
				{
					if($final_keywords[$i] == $word) unset($final_keywords[$i]);
				}
			}
			return $final_keywords;
		}
		
		/**
		 * Sorts keyword by the number of occurance
		 */
		function keyword_count_sort($first, $sec)
		{
			return $sec[1] - $first[1];
		}
	}
}

$AutoMetaKeywords = new AutoMetaKeywords(); 


