package umls;

import java.io.IOException;
import java.util.ArrayList;
import java.util.Arrays;
import java.util.HashMap;
import java.util.List;
import java.util.Map;

import org.apache.hadoop.conf.Configuration;
import org.apache.hadoop.fs.Path;
import org.apache.hadoop.io.IntWritable;
import org.apache.hadoop.io.LongWritable;
import org.apache.hadoop.io.Text;

import org.apache.mahout.classifier.naivebayes.BayesUtils;
import org.apache.mahout.classifier.naivebayes.NaiveBayesModel;
import org.apache.mahout.classifier.naivebayes.StandardNaiveBayesClassifier;
import org.apache.mahout.common.Pair;
import org.apache.mahout.common.iterator.sequencefile.SequenceFileIterable;
import org.apache.mahout.math.RandomAccessSparseVector;
import org.apache.mahout.math.Vector;
import org.apache.mahout.vectorizer.TFIDF;

import com.google.common.collect.ConcurrentHashMultiset;
import com.google.common.collect.Multiset;

public class SemanticTypeClassifier {
	public static Map<String, Integer> readDictionary(Configuration conf, Path dictionaryPath)
	{
		Map<String,Integer> dictionary = new HashMap<>();
		for(Pair<Text,IntWritable> pair : new SequenceFileIterable<Text,IntWritable>(dictionaryPath,true,conf))
		{
			dictionary.put(pair.getFirst().toString(), pair.getSecond().get());
		}
		return dictionary;
	}
	public static Map<Integer, Long> readDocumentFrequency(Configuration conf, Path documentFrequencyPath)
	{
		Map<Integer,Long> documentFrequency = new HashMap<>();
		for(Pair<IntWritable, LongWritable> pair : new SequenceFileIterable<IntWritable, LongWritable>(documentFrequencyPath,true,conf))
		{
			documentFrequency.put(pair.getFirst().get(), pair.getSecond().get());
		}
		return documentFrequency;
	}
	public static List<String> getBigrams(String[] tokens)
	{
		List<String> bigrams = new ArrayList<>(Arrays.asList((tokens)));
		for(int i=0;i<tokens.length;i++)
		{
			for(int j=0;j<tokens.length;j++)
			{
				if(tokens[i]!=tokens[j])
				{
					bigrams.add(new String(tokens[i] + " " + tokens[j]));
				}
			}
		}
		return bigrams;
	}
	public static void main(String[] args) throws IllegalArgumentException, IOException {
		
		//List containing the words and bigrams
		List<String> strings = getBigrams(args);
		
		/******** Read the require files to run the classification ******/
		// Directory Containing the files
		String dir = "classification_files/";
		Configuration conf = new Configuration();
		// Location of the dictionary mapping a world to its id
		Path dicPath = new Path(dir + "dictionary");
		// Location of the document frequency file (# of documents a world appeared on)
		Path dfPath = new Path(dir + "document_frequency");
		// Importing the files into HashMaps
		Map<String,Integer> dictionary = readDictionary(conf,dicPath);
		Map<Integer,Long> documentFrequency = readDocumentFrequency(conf,dfPath);
		// Importing the labels into a HashMap
		Map<Integer,String> label = BayesUtils.readLabelIndex(conf, new Path(dir + "umls_label_index"));
		
		// Creating the model
		NaiveBayesModel model = NaiveBayesModel.materialize(new Path(dir + "umls_modelNB"), conf);
		StandardNaiveBayesClassifier classifier = new StandardNaiveBayesClassifier(model);
		
		// To parse and normalize the query
		//Analyzer analyzer = new EnglishAnalyzer();
		//TokenStream ts = analyzer.tokenStream("text",new StringReader(query));
		//CharTermAttribute termAtt = ts.addAttribute(CharTermAttribute.class);
		//ts.reset();
		
		Multiset<String> words = ConcurrentHashMultiset.create();
		int wordCount = 0;
		int documentCount = documentFrequency.get(-1).intValue();
		
		for(String string : strings) 
		{
			Integer wordID = dictionary.get(string);
			if(wordID != null)
			{
				//System.out.println(string);
				words.add(string);
				wordCount++;
			}
		}
		// create vector wordId => weight using tfidf
        Vector vector = new RandomAccessSparseVector(10000);
        TFIDF tfidf = new TFIDF();
        for (Multiset.Entry<String> entry:words.entrySet()) {
            String word = entry.getElement();
            int count = entry.getCount();
            Integer wordId = dictionary.get(word);
            Long freq = documentFrequency.get(wordId);
            double tfIdfValue = tfidf.calculate(count, freq.intValue(), wordCount, documentCount);
            vector.setQuick(wordId, tfIdfValue);
        }
        
     // With the classifier, we get one score for each label
        Vector resultVector = classifier.classifyFull(vector);
        double bestScore = -Double.MAX_VALUE;
        int bestCategoryId = -1;
        for(Vector.Element element: resultVector.all())
        {
        	int categoryID = element.index();
        	double score = element.get();
        	if(score > bestScore)
        	{
        		bestScore = score;
        		bestCategoryId = categoryID;
        	}
        }
        if(vector.getNumNonZeroElements() > 0)
        {
        	System.out.println(label.get(bestCategoryId));
        }
        else
        {
        	System.out.println("T000");
        }
	}

}
