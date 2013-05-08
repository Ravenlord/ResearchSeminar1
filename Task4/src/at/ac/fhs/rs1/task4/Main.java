package at.ac.fhs.rs1.task4;

import java.sql.Connection;
import java.sql.DriverManager;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.sql.Statement;
import java.util.SortedSet;
import java.util.TreeSet;
import java.util.Vector;

import at.ac.fhs.rs1.task4.algorithm.mve.MVEAlgorithm;

import Jama.Matrix;

public class Main {

	private static Matrix retrieveGramMatrix(Connection connection) throws SQLException {
		int maxId = 0;
		// Retrieve the highest movie id from the distances table.
		Statement stmt = connection.createStatement();
		ResultSet rs = stmt.executeQuery("SELECT max(node2) as max FROM distances");
		while(rs.next()) {
			maxId = rs.getInt("max");
		}
		rs.close();
		maxId++;
		// Create a double[][] with dimension maxId.
		double[][] distanceMatrix = new double[maxId][maxId];
		// This set holds all valid ids of movies.
		SortedSet<Integer> validIds = new TreeSet<Integer>();
		rs = stmt.executeQuery("SELECT * FROM distances ORDER BY node1 ASC, node2 ASC");
		while(rs.next()) {
			// Fill the distance array with distances <= 150 (for sparsity).
			int node1 = rs.getInt("node1");
			int node2 = rs.getInt("node2");
			double distance = rs.getDouble("distance");
			validIds.add(node1);
			validIds.add(node2);
			if(distance <= 150) {
				distanceMatrix[node1][node2] = distance;
				distanceMatrix[node2][node1] = distance;
			} else {
				distanceMatrix[node1][node2] = 0d;
				distanceMatrix[node2][node1] = 0d;
			}
		}
		rs.close();
		stmt.close();
		// Construct a compact double matrix with no blanks.
		double[][] distancesCompact = new double[validIds.size()][validIds.size()];
		// Counters for determining the indexes in the new compacted distance matrix.
		int i = 0;
		int j = 0;
		// Row iteration which determines the first offset in the distances matrix.
		for(int node1 : validIds) {
			j = 0;
			// Column iteration which determines the second offset in the distances matrix.
			for(int node2 : validIds) {
				distancesCompact[i][j] = distanceMatrix[node1][node2];
				j++;
			}
			i++;
		}
		Matrix gramMatrix = new Matrix(distancesCompact);
		return gramMatrix;
	}
	
	/**
	 * @param args
	 */
	public static void main(String[] args) {
		try {
			Class.forName("com.mysql.jdbc.Driver");
			Connection connection = DriverManager.getConnection("jdbc:mysql://localhost:3306/movie_lens", "root", "");
			Matrix gramMatrix = Main.retrieveGramMatrix(connection);
			connection.close();
			MVEAlgorithm algorithm = new MVEAlgorithm(gramMatrix);
			int d = 1;
			double beta = 2;
			double kappa = 0;
			Vector<Double> result = algorithm.process(d, beta, kappa);
			for(double num : result) {
				System.out.println(num);
			}
		} catch (SQLException | ClassNotFoundException e) {
			// TODO Auto-generated catch block
			e.printStackTrace();
		}
	}

}
