<?php

/*
 * Copyright (c) 2011, predakanga
 * All rights reserved.
 * 
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *     * Redistributions of source code must retain the above copyright
 *       notice, this list of conditions and the following disclaimer.
 *     * Redistributions in binary form must reproduce the above copyright
 *       notice, this list of conditions and the following disclaimer in the
 *       documentation and/or other materials provided with the distribution.
 *     * Neither the name of the <organization> nor the
 *       names of its contributors may be used to endorse or promote products
 *       derived from this software without specific prior written permission.
 * 
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL <COPYRIGHT HOLDER> BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

namespace Fossil\Plugins\Search_solr;

use Fossil\OM,
    Fossil\Plugins\Search\BaseSearchBackend,
    Fossil\Plugins\Search\ISearchable,
    Fossil\Plugins\Search\SearchResult,
    Fossil\Models\PaginationProxy,
    \SolrClient,
    \SolrQuery,
    \SolrInputDocument;

/**
 * Description of SolrSearchBackend
 *
 * @author predakanga
 */
class SolrSearchBackend extends BaseSearchBackend {
    /** @var SolrClient */
    protected $solr;
    
    static function usable() {
        return function_exists("solr_get_version");
    }
    static function getName() {
        return "Apache Solr search backend";
    }
    static function getVersion() {
        return "1.4";
    }
    static function getFormName() {
        return null;
    }
    protected function getDefaultConfig() {
        return array('host' => 'localhost',
                     'port' => 8983);
    }
    
    protected function getFieldName($idxName, $field, $type) {
        $fieldName = $idxName . "_" . $field . "_";

        // Append type string
        if($type & ISearchable::SEARCH_FIELD_TEXT) {
            if($type & ISearchable::SEARCH_FIELD_TOKENIZED) {
                $fieldName .= "t";
            } else {
                $fieldName .= "s";
            }
        } elseif($type & ISearchable::SEARCH_FIELD_INT) {
            $fieldName .= "i";
        } elseif($type & ISearchable::SEARCH_FIELD_BINARY) {
            $fieldName .= "b";
        } elseif($type & ISearchable::SEARCH_FIELD_DATE) {
            $fieldName .= "d";
        }
        $fieldName .= "_";
        // Append index/storage strings
        if($type & ISearchable::SEARCH_FIELD_INDEXED) {
            $fieldName .= "i";
        }
        if($type & ISearchable::SEARCH_FIELD_STORED) {
            $fieldName .= "s";
        }
        return $fieldName;
    }
    protected function getID(ISearchable $entity) {
        $idxName = call_user_func(array($entity,"getIndexName"));
        $idField = call_user_func(array($entity,"getIDField"));
        
        return $idxName . "_" . $entity->{$idField};
    }
    
    public function __construct($container) {
        parent::__construct($container);
        
        $this->solr = new SolrClient($this->config);
    }
    public function clearIndexes($indexes) {
        $this->solr->deleteByQuery("*:*");
    }
    public function indexEntity(ISearchable $entity) {
        $doc = new SolrInputDocument();
        $idxName = call_user_func(array($entity,"getIndexName"));
        $types = call_user_func(array($entity,"getSearchFields"));
        
        $id = $this->getID($entity);
        $doc->addField("type", $idxName);
        $doc->addField("id", $this->getID($entity));
        foreach($types as $field => $type) {
            $typeAccessor = $field;
            if(isset($type['accessor'])) {
                $typeAccessor = $type['accessor'];
            }
            if($type['options'] & ISearchable::BOOST_FIELD) {
                $doc->setBoost($this->getDataFromModel($entity, $typeAccessor));
                continue;
            }

            $fieldName = $this->getFieldName($idxName, $field, $type['options']);
            $doc->addField($fieldName, $this->getDataFromModel($entity, $typeAccessor));
        }
        $this->solr->addDocument($doc);
    }
    public function removeEntity(ISearchable $entity) {
        $this->solr->deleteById($this->getID($entity));
    }
    public function findEntity(ISearchable $entity) {
        $idxName = call_user_func(array($entity,"getIndexName"));
        $types = call_user_func(array($entity,"getSearchFields"));

        $query = new SolrQuery();
        $id = $this->getID($entity);
        $query->setStart(0);
        $query->setRows(1);
        $query->setQuery("id:$id");
        $query->addField("id")->addField("type")->addField("*");
        $queryResponse = $this->solr->query($query);
        if(!$queryResponse->success()) {
            return null;
        }
        $response = $queryResponse->getResponse();
        $response = $response->response;
        if($response->numFound >= 1) {
            $doc = $response->docs[0];
            return array($doc->id, $doc);
        }
        return null;
    }
    public function updateEntity(ISearchable $entity) {
        $this->indexEntity($entity);
    }
    
    public function optimizeIndex($indexName) {
        $this->solr->optimize();
    }
    public function flush() {
        $this->solr->commit();
    }
    public function getSchemas($entities) {
        $typeXML = <<<XML
  <types>
    <!-- The StrField type is not analyzed, but indexed/stored verbatim. -->
    <fieldType name="string" class="solr.StrField" sortMissingLast="true" omitNorms="true"/>

    <!-- boolean type: "true" or "false" -->
    <fieldType name="boolean" class="solr.BoolField" sortMissingLast="true" omitNorms="true"/>
    <!--Binary data type. The data should be sent/retrieved in as Base64 encoded Strings -->
    <fieldtype name="binary" class="solr.BinaryField"/>

    <!--
      Default numeric field types. For faster range queries, consider the tint/tfloat/tlong/tdouble types.
    -->
    <fieldType name="int" class="solr.TrieIntField" precisionStep="0" omitNorms="true" positionIncrementGap="0"/>
    <fieldType name="float" class="solr.TrieFloatField" precisionStep="0" omitNorms="true" positionIncrementGap="0"/>
    <fieldType name="long" class="solr.TrieLongField" precisionStep="0" omitNorms="true" positionIncrementGap="0"/>
    <fieldType name="double" class="solr.TrieDoubleField" precisionStep="0" omitNorms="true" positionIncrementGap="0"/>

    <!-- The format for this date field is of the form 1995-12-31T23:59:59Z, and
         is a more restricted form of the canonical representation of dateTime
         http://www.w3.org/TR/xmlschema-2/#dateTime    
         The trailing "Z" designates UTC time and is mandatory.
         Optional fractional seconds are allowed: 1995-12-31T23:59:59.999Z
         All other components are mandatory.

         Expressions can also be used to denote calculations that should be
         performed relative to "NOW" to determine the value, ie...

               NOW/HOUR
                  ... Round to the start of the current hour
               NOW-1DAY
                  ... Exactly 1 day prior to now
               NOW/DAY+6MONTHS+3DAYS
                  ... 6 months and 3 days in the future from the start of
                      the current day
                      
         Consult the DateField javadocs for more information.

         Note: For faster range queries, consider the tdate type
      -->
    <fieldType name="date" class="solr.TrieDateField" omitNorms="true" precisionStep="0" positionIncrementGap="0"/>

    <!-- solr.TextField allows the specification of custom text analyzers
         specified as a tokenizer and a list of token filters. Different
         analyzers may be specified for indexing and querying.

         The optional positionIncrementGap puts space between multiple fields of
         this type on the same document, with the purpose of preventing false phrase
         matching across fields.

         For more info on customizing your analyzer chain, please see
         http://wiki.apache.org/solr/AnalyzersTokenizersTokenFilters
     -->
     
    <!-- A text field that only splits on whitespace for exact matching of words -->
    <fieldType name="text_ws" class="solr.TextField" positionIncrementGap="100">
      <analyzer>
        <tokenizer class="solr.WhitespaceTokenizerFactory"/>
      </analyzer>
    </fieldType>

    <!-- A general text field that has reasonable, generic
         cross-language defaults: it tokenizes with StandardTokenizer,
     removes stop words from case-insensitive "stopwords.txt"
     (empty by default), and down cases.  At query time only, it
     also applies synonyms. -->
    <fieldType name="text_general" class="solr.TextField" positionIncrementGap="100">
      <analyzer type="index">
        <tokenizer class="solr.StandardTokenizerFactory"/>
        <filter class="solr.StopFilterFactory" ignoreCase="true" words="stopwords.txt" enablePositionIncrements="true" />
        <filter class="solr.LowerCaseFilterFactory"/>
      </analyzer>
      <analyzer type="query">
        <tokenizer class="solr.StandardTokenizerFactory"/>
        <filter class="solr.StopFilterFactory" ignoreCase="true" words="stopwords.txt" enablePositionIncrements="true" />
        <filter class="solr.SynonymFilterFactory" synonyms="synonyms.txt" ignoreCase="true" expand="true"/>
        <filter class="solr.LowerCaseFilterFactory"/>
      </analyzer>
    </fieldType>

    <!-- A text field with defaults appropriate for English: it
         tokenizes with StandardTokenizer, removes English stop words
         (stopwords_en.txt), down cases, protects words from protwords.txt, and
         finally applies Porter's stemming.  The query time analyzer
         also applies synonyms from synonyms.txt. -->
    <fieldType name="text_en" class="solr.TextField" positionIncrementGap="100">
      <analyzer type="index">
        <charFilter class="solr.MappingCharFilterFactory" mapping="mapping-ISOLatin1Accent.txt"/>
        <tokenizer class="solr.StandardTokenizerFactory"/>
        <!-- Case insensitive stop word removal.
          add enablePositionIncrements=true in both the index and query
          analyzers to leave a 'gap' for more accurate phrase queries.
        -->
        <filter class="solr.StopFilterFactory"
                ignoreCase="true"
                words="stopwords_en.txt"
                enablePositionIncrements="true"
                />
        <filter class="solr.LowerCaseFilterFactory"/>
        <filter class="solr.EnglishPossessiveFilterFactory"/>
        <filter class="solr.KeywordMarkerFilterFactory" protected="protwords.txt"/>
        <filter class="solr.PorterStemFilterFactory"/>
      </analyzer>
      <analyzer type="query">
        <charFilter class="solr.MappingCharFilterFactory" mapping="mapping-ISOLatin1Accent.txt"/>
        <tokenizer class="solr.StandardTokenizerFactory"/>
        <filter class="solr.SynonymFilterFactory" synonyms="synonyms.txt" ignoreCase="true" expand="true"/>
        <filter class="solr.StopFilterFactory"
                ignoreCase="true"
                words="stopwords_en.txt"
                enablePositionIncrements="true"
                />
        <filter class="solr.LowerCaseFilterFactory"/>
        <filter class="solr.EnglishPossessiveFilterFactory"/>
        <filter class="solr.KeywordMarkerFilterFactory" protected="protwords.txt"/>
        <filter class="solr.PorterStemFilterFactory"/>
      </analyzer>
    </fieldType>

    <!-- A text field with defaults appropriate for English, plus
     aggressive word-splitting and autophrase features enabled.
     This field is just like text_en, except it adds
     WordDelimiterFilter to enable splitting and matching of
     words on case-change, alpha numeric boundaries, and
     non-alphanumeric chars.  This means certain compound word
     cases will work, for example query "wi fi" will match
     document "WiFi" or "wi-fi".  However, other cases will still
     not match, for example if the query is "wifi" and the
     document is "wi fi" or if the query is "wi-fi" and the
     document is "wifi".
        -->
    <fieldType name="text_en_splitting" class="solr.TextField" positionIncrementGap="100" autoGeneratePhraseQueries="true">
      <analyzer type="index">
        <charFilter class="solr.MappingCharFilterFactory" mapping="mapping-ISOLatin1Accent.txt"/>
        <tokenizer class="solr.WhitespaceTokenizerFactory"/>
        <filter class="solr.StopFilterFactory"
                ignoreCase="true"
                words="stopwords_en.txt"
                enablePositionIncrements="true"
                />
        <filter class="solr.WordDelimiterFilterFactory" generateWordParts="1" generateNumberParts="1" catenateWords="1" catenateNumbers="1" catenateAll="0" splitOnCaseChange="1"/>
        <filter class="solr.LowerCaseFilterFactory"/>
        <filter class="solr.KeywordMarkerFilterFactory" protected="protwords.txt"/>
        <filter class="solr.PorterStemFilterFactory"/>
      </analyzer>
      <analyzer type="query">
        <charFilter class="solr.MappingCharFilterFactory" mapping="mapping-ISOLatin1Accent.txt"/>
        <tokenizer class="solr.WhitespaceTokenizerFactory"/>
        <filter class="solr.SynonymFilterFactory" synonyms="synonyms.txt" ignoreCase="true" expand="true"/>
        <filter class="solr.StopFilterFactory"
                ignoreCase="true"
                words="stopwords_en.txt"
                enablePositionIncrements="true"
                />
        <filter class="solr.WordDelimiterFilterFactory" generateWordParts="1" generateNumberParts="1" catenateWords="0" catenateNumbers="0" catenateAll="0" splitOnCaseChange="1"/>
        <filter class="solr.LowerCaseFilterFactory"/>
        <filter class="solr.KeywordMarkerFilterFactory" protected="protwords.txt"/>
        <filter class="solr.PorterStemFilterFactory"/>
      </analyzer>
    </fieldType>

    <!-- Less flexible matching, but less false matches.  Probably not ideal for product names,
         but may be good for SKUs.  Can insert dashes in the wrong place and still match. -->
    <fieldType name="text_en_splitting_tight" class="solr.TextField" positionIncrementGap="100" autoGeneratePhraseQueries="true">
      <analyzer>
        <charFilter class="solr.MappingCharFilterFactory" mapping="mapping-ISOLatin1Accent.txt"/>
        <tokenizer class="solr.WhitespaceTokenizerFactory"/>
        <filter class="solr.SynonymFilterFactory" synonyms="synonyms.txt" ignoreCase="true" expand="false"/>
        <filter class="solr.StopFilterFactory" ignoreCase="true" words="stopwords_en.txt"/>
        <filter class="solr.WordDelimiterFilterFactory" generateWordParts="0" generateNumberParts="0" catenateWords="1" catenateNumbers="1" catenateAll="0"/>
        <filter class="solr.LowerCaseFilterFactory"/>
        <filter class="solr.KeywordMarkerFilterFactory" protected="protwords.txt"/>
        <filter class="solr.EnglishMinimalStemFilterFactory"/>
        <!-- this filter can remove any duplicate tokens that appear at the same position - sometimes
             possible with WordDelimiterFilter in conjuncton with stemming. -->
        <filter class="solr.RemoveDuplicatesTokenFilterFactory"/>
      </analyzer>
    </fieldType>

    <!-- Just like text_general except it reverses the characters of
     each token, to enable more efficient leading wildcard queries. -->
    <fieldType name="text_general_rev" class="solr.TextField" positionIncrementGap="100">
      <analyzer type="index">
        <tokenizer class="solr.StandardTokenizerFactory"/>
        <filter class="solr.StopFilterFactory" ignoreCase="true" words="stopwords.txt" enablePositionIncrements="true" />
        <filter class="solr.LowerCaseFilterFactory"/>
        <filter class="solr.ReversedWildcardFilterFactory" withOriginal="true"
           maxPosAsterisk="3" maxPosQuestion="2" maxFractionAsterisk="0.33"/>
      </analyzer>
      <analyzer type="query">
        <tokenizer class="solr.StandardTokenizerFactory"/>
        <filter class="solr.SynonymFilterFactory" synonyms="synonyms.txt" ignoreCase="true" expand="true"/>
        <filter class="solr.StopFilterFactory" ignoreCase="true" words="stopwords.txt" enablePositionIncrements="true" />
        <filter class="solr.LowerCaseFilterFactory"/>
      </analyzer>
    </fieldType>

    <!-- charFilter + WhitespaceTokenizer  -->
    <!--
    <fieldType name="text_char_norm" class="solr.TextField" positionIncrementGap="100" >
      <analyzer>
        <charFilter class="solr.MappingCharFilterFactory" mapping="mapping-ISOLatin1Accent.txt"/>
        <tokenizer class="solr.WhitespaceTokenizerFactory"/>
      </analyzer>
    </fieldType>
    -->

    <!-- This is an example of using the KeywordTokenizer along
         With various TokenFilterFactories to produce a sortable field
         that does not include some properties of the source text
      -->
    <fieldType name="alphaOnlySort" class="solr.TextField" sortMissingLast="true" omitNorms="true">
      <analyzer>
        <!-- KeywordTokenizer does no actual tokenizing, so the entire
             input string is preserved as a single token
          -->
        <tokenizer class="solr.KeywordTokenizerFactory"/>
        <!-- The LowerCase TokenFilter does what you expect, which can be
             when you want your sorting to be case insensitive
          -->
        <filter class="solr.LowerCaseFilterFactory" />
        <!-- The TrimFilter removes any leading or trailing whitespace -->
        <filter class="solr.TrimFilterFactory" />
        <!-- The PatternReplaceFilter gives you the flexibility to use
             Java Regular expression to replace any sequence of characters
             matching a pattern with an arbitrary replacement string, 
             which may include back references to portions of the original
             string matched by the pattern.
             
             See the Java Regular Expression documentation for more
             information on pattern and replacement string syntax.
             
             http://java.sun.com/j2se/1.6.0/docs/api/java/util/regex/package-summary.html
          -->
        <filter class="solr.PatternReplaceFilterFactory"
                pattern="([^a-z])" replacement="" replace="all"
        />
      </analyzer>
    </fieldType>
    
    <fieldtype name="phonetic" stored="false" indexed="true" class="solr.TextField" >
      <analyzer>
        <tokenizer class="solr.StandardTokenizerFactory"/>
        <filter class="solr.DoubleMetaphoneFilterFactory" inject="false"/>
      </analyzer>
    </fieldtype>

    <fieldtype name="payloads" stored="false" indexed="true" class="solr.TextField" >
      <analyzer>
        <tokenizer class="solr.WhitespaceTokenizerFactory"/>
        <!--
        The DelimitedPayloadTokenFilter can put payloads on tokens... for example,
        a token of "foo|1.4"  would be indexed as "foo" with a payload of 1.4f
        Attributes of the DelimitedPayloadTokenFilterFactory : 
         "delimiter" - a one character delimiter. Default is | (pipe)
     "encoder" - how to encode the following value into a playload
        float -> org.apache.lucene.analysis.payloads.FloatEncoder,
        integer -> o.a.l.a.p.IntegerEncoder
        identity -> o.a.l.a.p.IdentityEncoder
            Fully Qualified class name implementing PayloadEncoder, Encoder must have a no arg constructor.
         -->
        <filter class="solr.DelimitedPayloadTokenFilterFactory" encoder="float"/>
      </analyzer>
    </fieldtype>

    <!-- lowercases the entire field value, keeping it as a single token.  -->
    <fieldType name="lowercase" class="solr.TextField" positionIncrementGap="100">
      <analyzer>
        <tokenizer class="solr.KeywordTokenizerFactory"/>
        <filter class="solr.LowerCaseFilterFactory" />
      </analyzer>
    </fieldType>

    <fieldType name="text_path" class="solr.TextField" positionIncrementGap="100">
      <analyzer>
        <tokenizer class="solr.PathHierarchyTokenizerFactory"/>
      </analyzer>
    </fieldType>

    <!-- since fields of this type are by default not stored or indexed,
         any data added to them will be ignored outright.  --> 
    <fieldtype name="ignored" stored="false" indexed="false" multiValued="true" class="solr.StrField" />
 </types>
XML;
        $ourFields = "";
        $copyFields = "";
        foreach($entities as $entity) {
            $idxName = call_user_func(array($entity,"getIndexName"));
            $idField = call_user_func(array($entity,"getIDField"));
            $types = call_user_func(array($entity,"getSearchFields"));
            foreach($types as $field => $type) {
                $type = $type['options'];
                $fieldName = $this->getFieldName($idxName, $field, $type);
                // Append type string
                if($type & ISearchable::SEARCH_FIELD_TEXT) {
                    if($type & ISearchable::SEARCH_FIELD_TOKENIZED) {
                        $typeStr = "text_en";
                    } else {
                        $typeStr = "string";
                    }
                } elseif($type & ISearchable::SEARCH_FIELD_INT) {
                    $typeStr = "int";
                } elseif($type & ISearchable::SEARCH_FIELD_BINARY) {
                    $typeStr = "binary";
                } elseif($type & ISearchable::SEARCH_FIELD_DATE) {
                    $typeStr = "date";
                }
                // Append index/storage strings
                if($type & ISearchable::SEARCH_FIELD_INDEXED) {
                    $iStr = "true";
                } else {
                    $iStr = "false";
                }
                if($type & ISearchable::SEARCH_FIELD_STORED) {
                    $sStr = "true";
                } else {
                    $sStr = "false";
                }
                
                if($type & ISearchable::SEARCH_FIELD_DEFAULT_SEARCH) {
                    $copyFields .= "  <copyField source=\"$fieldName\" dest=\"text\" />\n";
                }
                $ourFields .= "    <field name=\"$fieldName\" type=\"$typeStr\" indexed=\"$iStr\" stored=\"$sStr\" />\n";
            }
        }
        $dynFields = "";
        foreach(array("string", "text_en", "binary", "int", "date") as $type) {
            foreach(array(true, false) as $indexed) {
                foreach(array(true, false) as $stored) {
                    if($indexed||$stored) {
                        $nameStr = "*_" . $type[0] . "_";
                        if($indexed) {
                            $nameStr .= "i"; $iStr = "true";
                        } else {
                            $iStr = "false";
                        }
                        if($stored) {
                            $nameStr .= "s"; $sStr = "true";
                        } else {
                            $sStr = "false";
                        }
                        $dynFields .= "    <dynamicField name=\"$nameStr\" type=\"$type\" indexed=\"$iStr\" stored=\"$sStr\" />\n";
                    }
                }
            }
        }
        $fields = <<<XML
  <fields>
    <field name="type" type="string" indexed="true" stored="true" required="true" />
    <field name="id" type="string" indexed="true" stored="true" required="true" />
    <field name="text" type="text_en" indexed="true" stored="true" multiValued="true" />
$ourFields
$dynFields
  </fields>
XML;
        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8" ?>
<schema name="main" version="1.4">
$typeXML
    
$fields
  <uniqueKey>id</uniqueKey>
  <defaultSearchField>text</defaultSearchField>
$copyFields
  <solrQueryParser defaultOperator="OR"/>
</schema>
XML;
        $schemaDir = OM::FS()->tempDir() . D_S . "search" . D_S . "solr" . D_S . "conf";
        if(!file_exists($schemaDir)) {
            mkdir($schemaDir, 0755, true);
        }
        $schemaFilename = $schemaDir . D_S . "schema.xml";
        file_put_contents($schemaFilename, $xml);
        return array($schemaFilename);
    }
    
    public function search($model, $queryStr, $returnRaw = false, $boosts = array()) {
        $idxName = call_user_func(array($model,"getIndexName"));
        $types = call_user_func(array($model,"getSearchFields"));
        $fieldMappings = array();

        $query = new SolrQuery();
        $query->setStart(0);
        $query->addFilterQuery("type:$idxName");
        $query->setQuery($queryStr);
        $query->addField("id")->addField("type");
        foreach($types as $field => $type) {
            $fieldName = $this->getFieldName($idxName, $field, $type['options']);
            if(!($type['options'] & ISearchable::SEARCH_FIELD_STORED)) {
                continue;
            }
            $fieldMappings[$field] = $fieldName;
            $query->addField($fieldName);
        }
        $query->setParam("defType", "edismax");
        if(count($boosts)) {
            $boostStr = "";
            foreach($boosts as $name => $factor) {
                if($boostStr != "") {
                    $boostStr .= "+";
                }
                $docName = $fieldMappings[$name];
                $boostStr .= "$docName^$factor";
            }
            $query->setParam("qf", $boostStr);
        }
        
        $queryResponse = $this->solr->query($query);
        if(!$queryResponse->success()) {
            return null;
        }
        $response = $queryResponse->getResponse();
        $response = $response->response;
        if($returnRaw) {
            return $response->docs;
        }
        $toRet = array();
        if(!$response->docs) {
            return $toRet;
        }
        foreach($response->docs as $doc) {
            $data = array();
            $idParts = explode("_", $doc->id);
            $data['dbId'] = (int)$idParts[count($idParts)-1];
            foreach($fieldMappings as $dataField => $docField) {
                $data[$dataField] = $doc->{$docField};
            }
            $toRet[] = new SearchResult($data);
        }
        return $toRet;
    }
    
    public function paginatedSearch($model, $query, $pageSize = 10) {
        $docs = $this->search($model, $query, true);
        if(!$docs) {
            return null;
        }
        $ids = array();
        foreach($docs as $doc) {
            $idParts = explode("_", $doc->id);
            $ids[] = (int)$idParts[1];
        }
        // Construct a query
        $builder = OM::ORM()->getEM()->createQueryBuilder();
        $builder = $builder->select("item")->from($model, "item")
                           ->where("item.id IN " . $builder->createPositionalParameter($ids));
        return new PaginationProxy($builder->getQuery(), $pageSize);
    }
}
